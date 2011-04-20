<?php
/**
* A sample soap server implementation.
*
* Usage:
* 1. This file should be copied to yourextension/soap/initialize.php
* 2. The extension yourextension should be declared as a SOAP extension (in soap.ini.append.php)
* 3. You should give proper access permissions to the users that have to access the webservices
*    (if your soap client does not support session cookie, give access permissions to the 'anonymous' role).
*    The name of the needed policy is "webservices"/"execute"
* 4. The helloWorld2.tpl file should be moved to yourextension/design/standard/webservices/wsdl
*    (and yourextension should be decalred as a design extension)
* 5. The wsdl for these services will be available at
*    http://your.server/index.php.siteaccess/webservices/wsdl
*    http://your.server/index.php.siteaccess/webservices/wsdl/helloWorld (for only the 1st service)
*    http://your.server/index.php.siteaccess/webservices/wsdl/helloWorld2 (for only the 2nd service)
*/

// 1st webservice: the wsdl will be built by eZ from the definition of the parameters we give
$server->registerFunction(
    'helloWorld', // name of the php function AND name of the webservice
    array( 'firstParam' => 'int', 'secondParam' => 'string', 'thirdParam' => 'bool | int' ), // list of input parameters: name => type
    'array of string' ); // output parameter

function helloWorld( $p1, $p2, $p3 )
{
    return array( 'hello' );
}

// 2nd webservice: the wsdl is managed by hand by the developer.
// Using a .tpl file to generate it makes it easy to have the correct url for the binding
$server->registerFunction(
    'helloWorld2', // name of the php function AND name of the webservice
    'design:/webservices/wsdl/helloWorld2.tpl' ); // the wsdl template

function helloWorld2( $p1, $p2, $p3 )
{
    return array( 'hello' );
}

?>