<?php
/**
 * View that executes webservice calls
 *
 * @author G. Giunta
 * @version $Id: proxy.php 86 2009-06-22 16:33:52Z gg $
 * @copyright (C) 2009-2012 G. Giunta
 */

// decode input params

$module = $Params['Module'];
$protocol = strtoupper( $Params['protocol'] );
$wsclass = $protocol;

switch( $protocol )
{
    case 'PHPSOAP':
        $protocol = 'SOAP';
        $wsclass = 'PhpSOAP';
        // continue voluntarily
    case 'REST':
    case 'JSONRPC':
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
    $serverClass = 'gg' . $wsclass . 'Server';
    $server = new $serverClass();

    // nb: this will register methods declared only for $protocol or for all
    //     protocols, depending on ini settings
    ggeZWebservices::registerAvailableMethods( $server, strtolower( $protocol ) );

    // from here onwards, we do the same as normally the server would do in a
    // single processRequest call. We need to add extra perms checking halfway
    // through, so we replicate the code here (we also add some ezjscore-support
    // hacks)

    $data = $server->inflateRequest( $data );
    if ( $data === false )
    {
        $server->showResponse(
            'unknown_function_name',
            $namespaceURI,
            new ggWebservicesFault( ggWebservicesServer::INVALIDCOMPRESSIONERROR, ggWebservicesServer::INVALIDCOMPRESSIONSTRING ) );
        eZExecution::cleanExit();
        die();
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

    if ( $protocol == 'REST' )
    {
        // hack! eZ is better at parsing the last path part than the REST request
        // on its own (in an eZP context...)
        $functionName = $Params['session'];
    }
    else
    {
        $functionName = $request->name();
    }
    $params = $request->parameters();

    $wsINI = eZINI::instance( 'wsproviders.ini' );

    // validate incoming IP address first
    if ( $wsINI->variable( 'GeneralSettings', 'ValidateClientIPs' ) == 'enabled' )
    {
        if ( !in_array( $_SERVER['REMOTE_ADDR'], $wsINI->variable( 'GeneralSettings', 'ValidClientIPs' ) ) )
        {
            // Error access denied - shall we show an error response in protocol format instead of html?
            // in that case, use an INVALIDAUTHERROR error code
            return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
        }
    }

    // if integration with jscore is enabled, look up function there
    // NB: ezjscServerRouter::getInstance does internally perms checking,  but
    // it does not return to us different values for method not found / perms not accorded
    if ( $wsINI->variable( 'GeneralSettings', 'JscoreIntegration' ) == 'enabled' && class_exists( 'ezjscServerRouter' ) )
    {
        if ( strpos( $functionName, '::' ) !== false )
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
    $access = ggeZWebservices::checkAccess( $functionName, $user );
    if ( !$access )
    {
        // Error access denied - shall we show an error response in protocol format instead of html?
        // in that case, use an INVALIDAUTHERROR error code
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

    if ( $wsclass == 'PhpSOAP' )
    {
        $server->processRequestObj( $request );
    }
    else
    {
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

}
eZExecution::cleanExit();

?>