<?php
/**
 * View that executes webservice calls
 *
 * @author G. Giunta
 * @copyright (C) 2009-2015 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
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
        echo ( $protocol == '' ) ? 'Protocol unspecified' : ( 'Unsupported protocol : ' . htmlspecialchars( $protocol ) );
        eZExecution::cleanExit();
        die();
}

$wsINI = eZINI::instance( ggeZWebservices::configFileByProtocol( strtolower( $protocol ) ) );
if ( $wsINI->variable( 'GeneralSettings', 'Enable' . $protocol ) != 'true' )
{
    /// @todo return an http error 500 or something like that ?
    echo ( 'Disabled protocol : ' . htmlspecialchars( $protocol ) );
    eZExecution::cleanExit();
    die();
}

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

// auth: validate incoming IP address first
if ( $wsINI->variable( 'GeneralSettings', 'ValidateClientIPs' ) == 'enabled' )
{
    $ip = is_callable( 'eZSys::clientIP' ) ? eZSys::clientIP() : eZSys::serverVariable( 'REMOTE_ADDR' );
    if ( !in_array( $ip, $wsINI->variable( 'GeneralSettings', 'ValidClientIPs' ) ) )
    {
        // Error: access denied. We respond using an answer which is correct according
        // to the protocol used by the caller, instead of going through the standard
        // eZ access denied error handler, which displays in general an html page
        // with a 200 OK http return code
        $server->showResponse(
            $functionName,
            $namespaceURI,
            new ggWebservicesFault( ggWebservicesServer::INVALIDAUTHERROR, ggWebservicesServer::INVALIDAUTHSTRING ) );
        eZExecution::cleanExit();
        die();
        // $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
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
    $server->showResponse(
        $functionName,
        $namespaceURI,
        new ggWebservicesFault( ggWebservicesServer::INVALIDAUTHERROR, ggWebservicesServer::INVALIDAUTHSTRING ) );
    eZExecution::cleanExit();
    die();
    // $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
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

eZExecution::cleanExit();

?>