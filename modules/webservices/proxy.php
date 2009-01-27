<?php
/**
 *
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright 2009
 */

// decode input params
$server = '';
$method = '';
$parameters = array();
$protocol = '';
$serverClass = 'gg' . $protocol . 'Server';
$responseClass = 'gg' . $protocol . 'Response';


// execute method, return response as object
// this also does validation of protocol name and server name
$response = ggeZWebservicesClient::send( $server, $method, $parameters, true );

if ( !is_object( $response ) )
{
    // invalid protocol name or server name
    switch( $protocol )
    {
        case 'REST':
        case 'JSONRPC':
        case 'SOAP':
        case 'XMLRPC' :
            $response = new $responseClass( $method );
            $response->setValue( new ggWebServicesFault( ggeZWebservicesClient::INVALIDSENDERROR, ggeZWebservicesClient::INVALIDSENDSTRING ) );
            break;
      default:
          /// @todo return an http error 500 or something like that ?
          echo 'Unsupported protocol : ' . $protocol;
          eZExecution::cleanExit();
          die();
    }
}
else
{
    if ( $response->faultCode() == ggeZWebservicesClient::INVALIDSENDERROR )
    {
        // protocol-level error
        // ...
    }
}

$server = new $serverClass( '' ); // avoid parsing raw_post_data
$server->showResponse( $method, ) // ...
eZExecution::cleanExit();

?>