<?php
/**
 * View that executes webservice calls
 *
 * @author G. Giunta
 * @version $Id: proxy.php 86 2009-06-22 16:33:52Z gg $
 * @copyright 2009
 */

// decode input params

$protocol = strtoupper( $Params['protocol'] );

switch( $protocol )
{
    //case 'REST':
    case 'JSONRPC':
    //case 'SOAP':
    case 'XMLRPC':
        //$data = file_get_contents( 'php://input' );
        break;
    default:
        /// @todo return an http error 500 or something like that ?
        echo 'Unsupported protocol : ' . $protocol;
        eZExecution::cleanExit();
        die();
}

$wsINI = eZINI::instance( 'wsproviders.ini' );
if ( $wsINI->variable( 'GeneralSettings', 'Enable' . $protocol ) == 'true' )
{
    // analyze request body

    $namespaceURI = '';
    $serverClass = 'gg' . $protocol . 'Server';
    $server = new $serverClass();

    foreach( $wsINI->variable( 'ExtensionSettings', $protocol . 'Extensions' ) as $extension )
    {
        include_once( eZExtension::baseDirectory() . '/' . $extension . '/' . strtolower( $protocol ) . '/initialize.php' );
    }

    $request = $server->parseRequest( $data );

    // invalid request: error out
    if ( !is_object( $request ) ) /// @todo use is_a instead
    {
        $server->showResponse(
            'unknown_function_name',
            $namespaceURI,
            new ggWebservicesFault( ggWebservicesServer::INVALIDREQUESTERROR, ggWebservicesServer::INVALIDREQUESTSTRING ) );
        eZExecution::cleanExit();
        die();
    }

    $functionName = $request->name();

    // if integration with jscore is enabled, look up function there
    if ( $wsINI->variable( 'GeneralSettings', 'JscoreIntegration' ) == 'enabled' && class_exists( 'ezjscServerRouter' ) )
    {
        if ( strpos( $functionName, '::' ) !== false)
        {
            $jscserver = ezjscServerRouter::getInstance( explode( '::', $functionName ) );
            if ( $jscserver != null )
            {
                $jscresponse = $jscserver->call();
                $server->showResponse( $functionName, $namespaceURI, $jscserver->call() );
                eZExecution::cleanExit();
                die();
            }
        }
    }

    // if jscore did not answer yet, process request the standard way
    if ( $server->isInternalRequest( $functionName ) )
    {
        $response = $server->handleInternalRequest( $functionName, $params );
    }
    else
    {
        $response = $server->handleRequest( $functionName, $params );
    }
    $server->showResponse( $functionName, $namespaceURI, $response );

}
eZExecution::cleanExit();

?>