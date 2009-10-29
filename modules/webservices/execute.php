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
        $data = file_get_contents( 'php://input' );
        break;
    default:
        /// @todo return an http error 500 or something like that ?
        echo 'Unsupported protocol : ' . $protocol;
        eZExecution::cleanExit();
        die();
}

$wsINI = eZINI::instance( ggeZWebservices::configFileByProtocol( strtolower( $protocol ) ) );
if ( $wsINI->variable( 'GeneralSettings', 'Enable' . $protocol ) == 'true' )
{
    $namespaceURI = '';
    $serverClass = 'gg' . $protocol . 'Server';
    $server = new $serverClass();

    ggeZWebservices::registerAvailableMethods( $server, strtolower( $protocol ) );

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
    $params = $request->parameters();

    // if integration with jscore is enabled, look up function there
    // NB: ezjscServerRouter::getInstance does internally perms checking,  but
    // it does not return to us different values for method not found / perms not accorded
    $wsINI = eZINI::instance( 'wsproviders.ini' );
    if ( $wsINI->variable( 'GeneralSettings', 'JscoreIntegration' ) == 'enabled' && class_exists( 'ezjscServerRouter' ) )
    {
        if ( strpos( $functionName, '::' ) !== false)
        {
            $jscserver = ezjscServerRouter::getInstance( array_merge( explode( '::', $functionName ), $params ) );
            if ( $jscserver != null )
            {
                $jscresponse = $jscserver->call();
                $server->showResponse( $functionName, $namespaceURI, $jscresponse );
                eZExecution::cleanExit();
                die();
            }
        }
    }

    // if jscore did not answer yet, process request the standard way

    // check perms
    $user = eZUser::currentUser();
    $accessResult = $user->hasAccessTo( 'webservices' , 'execute' );
    $accessWord = $accessResult['accessWord'];
    $access = false;
    if ( $accessWord == 'yes' )
    {
        $access = true;
    }
    else if ( $accessWord != 'no' ) // with limitation
    {
        //$policies = $accessResult['policies'];
        foreach ( $accessResult['policies'] as $key => $policy )
        {
            if ( isset( $policy['Webservices'] ) && in_array( $functionName, $policy['Webservices'] ) )
            {
                $access = true;
                break;
            }
        }
    }
    if ( !$access )
    {
        // Error access denied - shall we show an error response in protocol format instead of html?
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

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