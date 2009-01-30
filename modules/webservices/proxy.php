<?php
/**
 *
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright 2009
 *
 * @bug for xmlrpc, datetime and base64 parameters will be sent to remote server as strings...
 */

// decode input params

$protocol = $Params['protocol'];
$remoteserver = $Params['remoteServerName'];

switch( $protocol )
{
    //case 'REST':
    case 'JSONRPC':
    //case 'SOAP':
    case 'XMLRPC' :
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
$request = $this->parseRequest( $data );
if ( !is_object( $request ) ) /// @todo use is_a instead
{
    $server->showResponse(
        'unknown_function_name',
        $namespaceURI,
        new ggWebservicesFault( ggWebservicesResponse::INVALIDREQUESTERROR, ggWebservicesResponse::INVALIDREQUESTSTRING ) );
    eZExecution::cleanExit();
    die();
}

// execute method, return response as object
// this also does validation of server name
$response = ggeZWebservicesClient::send( $remoteserver, $request->name(), $request->parameters(), true );

if ( !is_object( $response ) )
{
    $server->showResponse(
        $request->name(),
        $namespaceURI,
        new ggWebservicesFault( ggeZWebservicesClient::INVALIDSENDERROR, ggeZWebservicesClient::INVALIDSENDSTRING ) );
}
else
{
    $server->showResponse( $request->name(), $namespaceURI, $response );
}

eZExecution::cleanExit();

?>