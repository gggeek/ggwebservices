<?php
/**
 * generic WebServices client, with ini-based setup and logging
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) 2009-2011 G. Giunta
 *
 * @todo move ini file name to class constant
 * @todo modify logging mechanism to use debug level instead of useless labels
 */

class ggeZWebservicesClient
{

    /**
    * The function called by the template fetch function webservices/call
    *
    * @todo allow template-level code to grab error messages if it wants to
    */
    static function call( $server, $method, $parameters = array(), $options = array() )
    {
        return self::send(  $server, $method, $parameters, false, $options );
    }

    /**
     * Sends a XML-RPC/JSON-RPC/SOAP/REST Request to the provider,
     * returning results in a correct format to be used in tpl fetch functions.
     * Optionally it returns the response object instead of the response value.
     * NB: for xmlrpc/jsonrpc/soap, all structured received data is converted to php arrays, not objects
     *
     * @param string $server provider name from the wsproviders.ini located in the extension's settings
     * @param string $method the webservice method to be executed
     * @param array $parameters parameters for the webservice method
     * @param boolean $return_response_obj
     * @param array $options extra options to be set into the ws client
     * @return array ( 'result' => null ) in case response is a fault one and $return_response_obj == false
     *
     * @bug returning NULL for non-error responses is fine only as long as the
     *      protocol does not permit empty responses
     *
     * @deprecated left here for backward compatibility - this API is crappy
     */
    static function send( $server, $method, $parameters, $return_response_obj = false, $options = array() )
    {
        try
        {
            $response = self::_call(  $server, $method, $parameters, $options );

            if  ( $return_response_obj )
            {
                return array( 'result' => $response );
            }

            if ( $response->isFault() )
            {
                 return array( 'result' => null );
            }

            return array( 'result' => $response->value() );
        }
        catch ( Exception $e )
        {
            /// @todo shall we return a resp. obj in $return_response_obj mode?
            return array( 'error' => $e->getMessage() );
        }
    }

    /**
     * This method sends a XML-RPC/JSON-RPC/SOAP/REST Request to the provider,
     * throwing an exception in case of major problems (ie. client-side errors)
     *
     * @param string $server provider name from the wsproviders.ini located in the extension's settings
     * @param string $method the webservice method to be executed
     * @param array $parameters parameters for the webservice method
     * @param array $options extra options to be set into the ws client
     * @return mixed an object instance of a ggWebservicesResponse subclass
     */
    static private function _call( $server, $method, $parameters, $options = array() )
    {

        // Gets provider's data from the conf
        $ini = eZINI::instance( 'wsproviders.ini' );

        /// check: if section $server does not exist, error out here
        if ( !$ini->hasGroup( $server ) )
        {
            ggeZWebservices::appendLogEntry( 'Trying to call service on undefined server: ' . $server, 'error' );
            throw new Exception( 'Trying to call service on undefined server: ' . $server );
        }
        $providerURI = $ini->variable( $server, 'providerUri' );
        $providerType = $ini->variable( $server, 'providerType' );
        $wsdl = $ini->hasVariable( $server, 'WSDL' ) ? $ini->variable( $server, 'WSDL' ) : '';

        /// @deprecated all of the ini vars in this block of code are deprecated
        $soapversion = ( $ini->hasVariable( $server, 'SoapVersion' ) && strtolower( $ini->variable( $server, 'SoapVersion' ) ) == 'soap12' ) ? 2 : 1; // work even if php soap ext. disabled
        //$providerAuthtype = $ini->hasVariable( $server, 'providerAuthtype' ) ? $ini->variable( $server, 'providerAuthtype' ) : false; /// @TODO: to be implemented
        //$providerSSLRequired = $ini->hasVariable( $server, 'providerSSLRequired' ) ? $ini->variable( $server, 'providerSSLRequired' ) : false; /// @TODO: to be implemented
        $providerUsername = $ini->hasVariable( $server, 'providerUsername' ) ? $ini->variable( $server, 'providerUsername' ) : false;
        $providerPassword = $ini->hasVariable( $server, 'providerPassword' ) ? $ini->variable( $server, 'providerPassword' ) : false;
        if ( $ini->hasVariable( $server, 'timeout' ) )
            $timeout = (int)$ini->variable( $server, 'timeout' );
        else
            $timeout = false;

        // 'new style' server config: make it easier to define any desired client setting,
        // even ones added in future releases, without having to parse it by hand in this class
        $providerOptions = $ini->hasVariable( $server, 'Options' ) ? $ini->variable( $server, 'Options' ) : array();

        // add the user-set options on top of the options set in ini file
        $providerOptions = array_merge( $providerOptions, $options );

        /// @todo add support for proxy config in either $providerOptions or $options

        // Proxy: if not specified per-target server, use global one
        $providerProxy = '';
        if ( !$ini->hasVariable( $server, 'ProxyServer' ) )
        {
            $ini = eZINI::instance( 'site.ini' );
            $group = 'ProxySettings';
            $proxyPrefix = '';
        }
        else
        {
            $group = $server;
            $proxyPrefix = 'Proxy';
        }
        if ( $ini->hasVariable( $group, 'ProxyServer' ) && $ini->variable( $group, 'ProxyServer' ) != '' )
        {
            $providerProxy = $ini->variable( $group, 'ProxyServer' );
            $providerProxyPort = explode( ':', $providerProxy );
            if ( count( $providerProxyPort ) > 1 )
            {
                $providerProxy = $providerProxyPort[0];
                $providerProxyPort = $providerProxyPort[1];
            }
            else
            {
                $providerProxyPort = 0;
            }
            $providerProxyUser = '';
            $providerProxyPassword = '';
            if ( $ini->hasVariable( $group, $proxyPrefix . 'User' ) )
            {
                $providerProxyUser = $ini->variable( $group, $proxyPrefix . 'User' );
                if ( $ini->hasVariable( $group, $proxyPrefix . 'Password' ) )
                {
                    $providerProxyPassword = $ini->variable( $group, $proxyPrefix . 'Password' );
                }
            }
        }

        $clientClass = 'gg' . $providerType . 'Client';
        $requestClass = 'gg' . $providerType . 'Request';
        $responseClass = 'gg' . $providerType . 'Response';

        switch( $providerType )
        {
        case 'REST':
        case 'JSONRPC':
        case 'SOAP':
        case 'PhpSOAP':
        case 'eZJSCore':
        case 'XMLRPC':
        case 'HTTP':
            $proxylog = '';
            if ( $providerProxy != '' )
            {
                $proxylog = "using proxy $providerProxy:$providerProxyPort";
            }
            $wsdllog = '';
            if ( $wsdl != '' )
            {
                $wsdllog = "(wsdl: $wsdl)";
            }
            ggeZWebservices::appendLogEntry( "Connecting to: $providerURI $wsdllog via $providerType $proxylog", 'debug' );
            if ( $providerURI != '' )
            {
                $url = parse_url( $providerURI );
                if ( !isset( $url['scheme'] ) || !isset( $url['host'] ) )
                {
                    ggeZWebservices::appendLogEntry( "Error in user request: bad server url $providerURI for server $server", 'error' );
                    throw new Exception( "Error in user request: bad server url $providerURI for server $server" );
                }
                if ( !isset( $url['path'] ) )
                {
                    $url['path'] = '/';
                }
                if ( !isset( $url['port'] ) )
                {
                    if ( $url['scheme'] == 'https' )
                    {
                        $url['port'] = 443;
                    }
                    else
                    {
                        $url['port'] = 80;
                    }
                }
            }
            else
            {
                if ( $wsdl != '' )
                {
                    $url = array( 'host' => '', 'path' => '', 'port' => 0, 'scheme' => null );
                }
                else
                {
                    ggeZWebservices::appendLogEntry( "Error in user request: no server url for server $server", 'error' );
                    throw new Exception( "Error in user request: no server url for server $server" );
                }
            }

            $client = new $clientClass( $url['host'], $url['path'], $url['port'], $url['scheme'], $wsdl );
            /// deprecated settings
            if ( $providerUsername != '' )
            {
                $client->setOptions( array( 'login' => $providerUsername, 'password' => $providerPassword ) );
            }
            if ( $timeout )
            {
                $client->setOption( 'timeout', $timeout );
            }
            if ( $providerType == 'PhpSOAP' )
            {
                $client->setOption( 'soapVersion', $soapversion );
            }
            /// other settings
            if ( $providerProxy != '' )
            {
                $client->setOptions( array( 'proxyHost' => $providerProxy, 'proxyPort' => $providerProxyPort, 'proxyUser' => $providerProxyUser, 'proxyPassword' => $providerProxyPassword ) );
            }
            if ( is_array( $providerOptions ) )
            {
                $client->setOptions( $providerOptions );
            }
            /// @todo shall we allow caller to override this setting?
            if ( ggeZWebservices::isLoggingEnabled( 'info' ) )
            {
                $client->setOption( 'debug', 2 );
            }

            if ( $providerType == 'SOAP' || $providerType == 'PhpSOAP' )
            {
                $namespace = null;
                if ( is_array( $method ) )
                {
                    $namespace = $method[1];
                    $method = $method[0];
                }
                $request = new $requestClass( $method, $parameters, $namespace );
            }
            else
            {
                $request = new $requestClass( $method, $parameters );
            }

            $response = $client->send( $request );

            if ( ggeZWebservices::isLoggingEnabled( 'info' ) )
            {
                ggeZWebservices::appendLogEntry( 'Sent: ' . $client->requestPayload(), 'info' );
                ggeZWebservices::appendLogEntry( 'Received: ' . $client->responsePayload(), 'info' );
            }

            if ( !is_object( $response ) )
            {
                ggeZWebservices::appendLogEntry( 'HTTP-level error ' . $client->errorNumber() . ': '. $client->errorString(), 'error' );
                $response = new $responseClass( $method );
                $response->setValue( new ggWebservicesFault( $client->errorNumber(), $client->errorString() ) );
                return $response;
            }

            unset( $client );
            if ( $response->isFault() )
            {
                ggeZWebservices::appendLogEntry( "$providerType protocol-level error " . $response->faultCode() . ':' . $response->faultString(), 'error' );
            }
            return $response;

        default:
            // unsupported protocol
            ggeZWebservices::appendLogEntry( "Error in user request: unsupported protocol $providerType", 'error' );
            throw new Exception( "Error in user request: unsupported protocol $providerType" );
        }
    }

}

?>