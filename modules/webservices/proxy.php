<?php
/**
 *
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @bug for xmlrpc, datetime and base64 parameters will be sent to remote server as strings...
 */

// decode input params

$module = $Params['Module'];
$protocol = strtoupper( $Params['protocol'] );
$remoteserver = $Params['remoteServerName'];

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

// analyze request body

$namespaceURI = '';
$serverClass = 'gg' . $protocol . 'Server';
$server = new $serverClass();
$request = $server->parseRequest( $data );
if ( !is_object( $request ) ) /// @todo use is_a instead
{
    $server->showResponse(
        'unknown_function_name',
        $namespaceURI,
        new ggWebservicesFault( ggWebservicesServer::INVALIDREQUESTERROR, ggWebservicesServer::INVALIDREQUESTSTRING ) );
    eZExecution::cleanExit();
    die();
}

// check perms
$user = eZUser::currentUser();
$access = ggeZWebservices::checkAccessToServer( $remoteserver, $user );
if ( !$access )
{
    // Error access denied
    return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
}

// execute method, return response as object
// this also does validation of server name
$response = ggeZWebservicesClient::send( $remoteserver, $request->name(), $request->parameters(), true );
$response = reset( $response );
if ( !is_object( $response ) )
{
    /// @todo the only case where we do not get back a response is when we get back an error string.
    ///       Shall we show that error string to the caller instead of the generic error string?
    $server->showResponse(
        $request->name(),
        $namespaceURI,
        new ggWebservicesFault( ggWebservicesServer::GENERICRESPONSEERROR, ggWebservicesServer::GENERICRESPONSESTRING ) );
}
else
{
    $server->showResponse( $request->name(), $namespaceURI, $response->value() );
}

eZExecution::cleanExit();

?>