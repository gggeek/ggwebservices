<?php
/**
 * Sample xmlrpc server that uses the ggws classes outside of an eZ Publish context
 *
 * @version $Id$
 * @author Gaetano Giunta
 * @copyright (c) 2010-2022 G. Giunta
 * @license code licensed under the GNU GPL. See LICENSE file
 */

// include client classes (this is done by autload when within an eZP context)
include_once( "ggwebservices/classes/ggwebservicesserver.php" );
include_once( "ggwebservices/classes/ggxmlrpcserver.php" );
include_once( "ggwebservices/classes/ggwebservicesrequest.php" );
include_once( "ggwebservices/classes/ggxmlrpcrequest.php" );
include_once( "ggwebservices/classes/ggwebservicesresponse.php" );
include_once( "ggwebservices/classes/ggxmlrpcresponse.php" );
include_once( "ggwebservices/classes/ggwebservicesfault.php" );

$server = new ggXMLRPCServer();
$server->registerFunction( "examples.addtwo", array( "valueA" => "int", "valueB" => "int" ) );
$server->processRequest();

function examples_addtwo( $valueA, $valueB )
{
    return (int)$valueA + (int)$valueB;
}
