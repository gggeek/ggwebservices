<?php
/**
 * Class used to communicate with soap servers via the native soap extension
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 * The BIG difference with the eZ soap class is that this one groks WSDL
 *
 * Known differences from other ws clients:
 * - the timeout set is not active while socket is open (reading/writing) but only
 *   for opening it
 */

class ggPhpSOAPClient extends ggWebservicesClient
{
    /**
    * The difference between this constructor and the parent one is the addition
    * of the wsdl parameter
    */
    function __construct( $server, $path = '/', $port = 80, $protocol=null, $wsdl=null )
    {
        /// @todo verify: the following 2 field is unused?
        $this->ContentType = 'text/xml'; /// @todo add UTF8 charset by default?
        $this->ResponseClass = 'ggPhpSOAPResponse';
        $this->UserAgent = 'gg eZ PHPSOAP client';
        $this->Wsdl = $wsdl;

        parent::__construct( $server, $path, $port, $protocol );

    }

    /// @todo add support for timeout during streaming and not only socket opening (involves changing ini value on the fly ?)
    /// @todo test if http-layer errors get wrapped in a soap fault or something else...
    function send( $request )
    {
        /// @todo add a check that request is a soap / phpsoap one, or it will have no namespace method...

        $options = array( 'trace' => true, 'user_agent' => $this->UserAgent, 'connection_timeout' => $this->Timeout, 'exceptions' => true );
        if ( $this->RequestCompression == 'deflate' )
        {
            $options['compression'] = SOAP_COMPRESSION_DEFLATE | 9;
        }
        else if ( $this->RequestCompression == 'gzip' )
        {
            $options['compression'] = SOAP_COMPRESSION_GZIP | 9;
        }
        if ( $this->Login != '' )
        {
            $options['login'] = $this->Login;
            $options['password'] = $this->Password;
        }
        if ( $this->Proxy != '' )
        {
            $options['proxy_host'] = $this->Proxy;
            $options['proxy_port'] = $this->ProxyPort;
            if ( $this->ProxyLogin != '' )
            {
                $options['proxy_login'] = $this->ProxyLogin;
                $options['proxy_password'] = $this->ProxyPassword;
            }
        }
        if ( $this->Wsdl == null )
        {
            // non-wsdl mode
            $options['location'] = $this->Protocol . "://" . $this->Server . ":" . $this->Port . $this->Path;
            $options['uri'] = $request->namespace();
        }
        else
        {
            if ( preg_match( '#^https?://#', $this->Wsdl ) && $this->Timeout != 0 )
            {
                // patch around buggy soapclient behaviour: force socket timeout on getting wsdl call
                $deftimeout = ini_get( 'default_socket_timeout' );
                if ( $deftimeout !=  $this->Timeout )
                {
                    ini_set( 'default_socket_timeout', $this->Timeout );
                }
                else
                {
                    unset( $deftimeout );
                }
            }

        }
        try
        {
            $response = new $this->ResponseClass();
            $client = @new SoapClient( $this->Wsdl, $options );
            if ( isset( $deftimeout ) )
            {
                ini_set( 'default_socket_timeout', $deftimeout );
            }
            $results = $client->__soapCall( $request->name(), $request->parameters(), array(), array(), $output_headers );
            //eZDebug::writeDebug( $client->__getLastRequest(), __METHOD__ );
            $this->requestPayload = $client->__getLastRequest();
            $response->decodeStream( null, $client->__getLastResponse() );
            if ( is_soap_fault( $results ) )
            {
                $response->setValue( new ggWebservicesFault( $result->faultcode, $result->faultstring ) );
            }
            else
            {
                $response->setValue( $results );
            }
            return $response;
        }
        catch( exception $e )
        {
            if ( $e instanceof SoapFault )
            {
                $response->setValue( new ggWebservicesFault( $e->faultcode, $e->faultstring ) );
            }
            else
            {
                $response->setValue( new ggWebservicesFault( $e->code, $e->message ) );
            }
            return $response;
        }

    }

    /**
     * Return request payload of last executed send call
     * NOTE: this is different from other client classes, as normally payload is generated
     * by request itself, but the php ext. API is different.
     */
    public function requestPayload()
    {
        return $this->requestPayload;
    }

    /// @todo override function payload() of parent and throw an exception when called, as we do not set up proper RequestHeaders anyway

    protected $Wsdl;
    protected $requestPayload = '';
}

?>