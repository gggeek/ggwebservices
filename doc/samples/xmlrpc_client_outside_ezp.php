<?php
/**
 * Sample xmlrpc client that uses the ggws classes outside of an eZ Publish context
 * The server endpoint in use is the public one of the phpxmlrpc.sourceforge.net lib
 *
 * @author Gaetano Giunta
 * @copyright (c) 2010-2013 G. Giunta
 * @license code licensed under the GNU GPL. See LICENSE file
 */

// include client classes (this is done by autload when within an eZP context)
include_once( "ggwebservices/classes/ggwebservicesclient.php" );
include_once( "ggwebservices/classes/ggwebservicesrequest.php" );
include_once( "ggwebservices/classes/ggxmlrpcrequest.php" );
include_once( "ggwebservices/classes/ggwebservicesresponse.php" );
include_once( "ggwebservices/classes/ggxmlrpcresponse.php" );

// create a new client
$client = new ggWebservicesClient( "phpxmlrpc.sourceforge.net", "/server.php" );

// define the request
$request = new ggXMLRPCRequest( "examples.addtwo", array( 44, 45 ) );

// send the request to the server and fetch the response
$response = $client->send( $request );

if ( !$response )
{
    print( "<pre>Error: " . $client->errorNumber(). " - \"" . $client->errorString() . "\"");
}
else
{
    // check if the server returned a fault, if not print out the result
    if ( $response->isFault() )
    {
        print( "<pre>Fault: " . $response->faultCode(). " - \"" . $response->faultString() . "\"");
    }
    else
    {
        print( "<pre>Returned value was: \"" . $response->value() . "\"" );
    }
}
?>
