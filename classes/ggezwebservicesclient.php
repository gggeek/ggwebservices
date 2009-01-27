<?php
/**
 * generic WebServices client, with ini-based setup and logging
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 * @todo move ini file name to class constant
 * @todo move log file name to ini entry
 * @todo modify logging mechanism to use debug level instead of useless labels
 */

class ggeZWebservicesClient
{
    const INVALIDSENDERROR = -1;
    const INVALIDSENDSTRING = 'Http communication with server failed';

    static $errorlevels = array(
        'info' => 6,
        'notice' => 5,
        'debug' => 4,
        'warning' => 3,
        'error' => 2,
        'critical' => 1,
        'none' => 0);

    static $debuglevel = -1;

    /**
     * This method sends a XML-RPC/JSON-RPC/SOAP Request to the provider,
     * returning results in a correct format to be used in tpl fetch functions
     * @param string $server provider name from the wsproviders.ini located in the extension's settings
     * @param string $method:
     * @param array $parameters
     * @param boolean $return_reponse_obj
     * @return array containing value 0 if method call failed, else plain php value (tbd: something more informative?)
     *
     * @bug returning 0 for non-error responses is fine as long as the protocol
     *      does not permit empty responses. This is not the case with json-rpc!
    */
    static function send( $server, $method, $parameters, $return_reponse_obj = false )
    {

        //include_once ("lib/ezutils/classes/ezini.php");

        //Gets provider's data from the conf
        $ini = eZINI::instance( 'wsproviders.ini' );

        /// check: if section $server does not exist, error out here
        if ( !$ini->hasGroup( $server ) )
        {
            ggeZWebservicesClient::appendLogEntry( 'Trying to call service on undefined server: ' . $server, 'error' );
            return array( 'error' => 'Trying to call service on undefined server: ' . $server, 'error' );
        }
        $providerURI = $ini->variable( $server, 'providerUri' );
        $providerType = $ini->variable( $server, 'providerType' );
        $providerAuthtype = $ini->hasVariable( $server, 'providerAuthtype' ) ? $ini->variable( $server, 'providerAuthtype' ) : false; /// @TODO: to be implemented
        $providerSSLRequired = $ini->hasVariable( $server, 'providerSSLRequired' ) ? $ini->variable( $server, 'providerSSLRequired' ) : false; /// @TODO: to be implemented
        $providerUsername = $ini->hasVariable( $server, 'providerUsername' ) ? $ini->variable( $server, 'providerUsername' ) : false;
        $providerPassword = $ini->hasVariable( $server, 'providerPassword' ) ? $ini->variable( $server, 'providerPassword' ) : false;
        if ( $ini->hasVariable( $server, 'timeout' ) )
            $timeout = (int)$ini->variable( $server, 'timeout' );
        else
            $timeout = false;

        $clientClass = 'gg' . $providerType . 'Client';
        $requestClass = 'gg' . $providerType . 'Request';
        $responseClass = 'gg' . $providerType . 'Response';

        switch($providerType){
        case 'REST':
        case 'JSONRPC':
        case 'SOAP':
        case 'XMLRPC' :
            ggeZWebservicesClient::appendLogEntry( "Connecting to: $providerURI via $providerType", 'debug' );
            $url = parse_url( $providerURI );
            if ( !isset( $url['port'] ) )
            {
                $url['port'] = 80;
            }
            $client = new $clientClass( $url['host'], $url['path'], $url['port'] );
            if ( $providerUsername != '' ) {
                $client->setCredentials( $providerUsername, $providerPassword );
            }
            if ( $timeout )
            {
                $client->setTimeout( $timeout );
            }

            if ( $providerType == 'SOAP' )
            {
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
            ggeZWebservicesClient::appendLogEntry( 'Sending: ' . $request->payload(), 'info' );
            $response = $client->send( $request );

            if ( !is_object( $response ) )
            {
                /*$code = WebServicesOperator :: getCodeError($err);
                $tab = array ('error' => $err, 'CodeError' => $code, 'parametres' => $parameters);
                if($DEBUG){print_r($tab);}*/

                ggeZWebservicesClient::appendLogEntry( 'Error in http communication with server: ' . $client->ErrorString, 'error' );

                unset( $client );
                if ( $return_reponse_obj )
                {
                    $response = new $responseClass( $method );
                    $response->setValue( new ggWebServicesFault( ggeZWebservicesClient::INVALIDSENDERROR, ggeZWebservicesClient::INVALIDSENDSTRING ) );
                }
                return array( 'result' => $response );
            }
            else
            {
                unset( $client );
                ggeZWebservicesClient::appendLogEntry( 'Received: ' . $response->rawResponse, 'info' );

                if ( $response->isFault() )
                {
                    ggeZWebservicesClient::appendLogEntry( "$providerType protocol-level error " . $response->faultCode(), 'error' );
                    if ( !$return_reponse_obj )
                        return array( 'result' => null );
                }

                if ( $return_reponse_obj )
                    return array( 'result' => $response );
                else
                    return array( 'result' => $response->value() );
            }

            break;

        default:
            // unsupported protocol
            ggeZWebservicesClient::appendLogEntry( 'Error in user request: unsupported protocol ' . $providerType, 'error' );
            return array( 'error' => 'Error in user request: unsupported protocol ' . $providerType, 'error' );
        }
    }

    /*function getCodeError($err)
    {

        $XMLFormat = 505;
        $Parametre = 606;
        $SocketConnection = 707;

        if (stristr($err, 'Erreur de param')) {
            return $Parametre;
        }
        if (stristr($err, 'Response not of type text/xml')) {
            return $XMLFormat;
        }
        if (stristr($err, 'open socket connection to server')) {
            return $SocketConnection;
        }
    }*/

    /**
      Logs the string $logString to the logfile webdav.log
      in the current log directory (usually var/log).
      If logging is disabled, nothing is done.
    */
    static function appendLogEntry( $logString, $debuglevel )
    {
        if ( !ggeZWebservicesClient::isLoggingEnabled( $debuglevel ) )
            return false;

        $varDir = eZSys::varDirectory();

        $logDir = 'log';
        $logName = 'webservices.log';
        $fileName = $varDir . '/' . $logDir . '/' . $logName;
        if ( !file_exists( $varDir . '/' . $logDir ) )
        {
            //include_once( 'lib/ezfile/classes/ezdir.php' );
            eZDir::mkdir( $varDir . '/' . $logDir, 0775, true );
        }

        if ( $logFile = fopen( $fileName, 'a' ) )
        {
            $nowTime = date( "Y-m-d H:i:s : " );
            $text = $nowTime . $logString;
            /*if ( $label )
                $text .= ' [' . $label . ']';*/
            fwrite( $logFile, $text . "\n" );
            fclose( $logFile );
        }
    }

    /**
      return true if logging is enabled.
    */
    static function isLoggingEnabled( $debuglevel )
    {
        $logging =& ggeZWebservicesClient::$debuglevel;
        if ( $logging < 0 )
        {
            $logging = 0; // shall we init to 1 or 2 ?
            $ini = eZINI::instance( 'wsproviders.ini' );
            if ( $ini->hasvariable( 'GeneralSettings', 'Logging' ) )
            {
                $level = $ini->variable( 'GeneralSettings', 'Logging' );
                if ( array_key_exists( $level, ggeZWebservicesClient::$errorlevels ) )
                {
                    $logging = ggeZWebservicesClient::$errorlevels[$level];
                }
            }
            //ggeZWebservicesClient::$debuglevel = $logging;
        }
        if ( !array_key_exists( $debuglevel, ggeZWebservicesClient::$errorlevels ) )
        {
            return false;
        }
        return ggeZWebservicesClient::$errorlevels[$debuglevel] <= $logging;
    }

}

?>
