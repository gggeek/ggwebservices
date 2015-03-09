<pre>
<?php
/**
 * Sample soap client that uses the ggws classes outside of an eZ Publish context
 * The server endpoint in use is the public one from mssoapinterop.org
 *
 * @author Gaetano Giunta
 * @copyright (c) 2010-2015 G. Giunta
 * @license code licensed under the GNU GPL. See LICENSE file
 */

// include client classes (this is done by autload when within an eZP context)
include_once( "ggwebservices/classes/ggwebservicesclient.php" );
include_once( "ggwebservices/classes/ggphpsoapclient.php" );
include_once( "ggwebservices/classes/ggphpsoapclienttransport.php" );
include_once( "ggwebservices/classes/ggwebservicesrequest.php" );
include_once( "ggwebservices/classes/ggsoaprequest.php" );
include_once( "ggwebservices/classes/ggphpsoaprequest.php" );
include_once( "ggwebservices/classes/ggwebservicesresponse.php" );
include_once( "ggwebservices/classes/ggphpsoapresponse.php" );
include_once( "ggwebservices/classes/ggwebservicesfault.php" );

// create a new client
$client = new ggPhpSOAPClient( "mssoapinterop.org", "/asmx/simple.asmx", 80, null, 'http://mssoapinterop.org/asmx/simple.asmx?WSDL' );
// NB: this also works:
//$client = new ggPhpSOAPClient(null, null, null , null, 'http://mssoapinterop.org/asmx/simple.asmx?WSDL');

// define the request
$request = new ggPhpSOAPRequest( "echoInteger", array( 123 ) );

// set client to debug mode
$client->setOption( 'debug', 2 );

// send the request to the server and fetch the response
$response = $client->send( $request );

// show debug info
echo 'SENT:  <div style="background-color: silver">' . htmlspecialchars( $client->RequestPayload() ) . "</div>";
echo 'RECEIVED: <div style="background-color: silver">' . htmlspecialchars( $client->ResponsePayload() ) . "</div>";

// check if the server returned a fault, if not print out the result
if ( $response->isFault() )
{
    print( "Fault: " . $response->faultCode(). " - \"" . $response->faultString() . "\"" );
}
else
{
    print( "Returned value was: \"" . $response->value() . "\"" );
}

?>
