<?php
/**
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

$Module = array( 'name' => 'Webservices', "variable_params" => true );

$ViewList = array(
    /*
    * View used to proxy webservices calls to other wervices, overcoming the same-domain limitation.
    * General scheme: call a jsonrpc or xmlrpc webservice via a js function in your templates pointing to /wsproxy/jsonrpc or /wsproxy/xmlrpc
    */
    'proxy' => array(
        'script' => 'proxy.php',
        'params' => array( 'protocol', 'remoteServerName' ),
        'functions' => array( 'proxy' ),
    ),

    /*
    * View used to invoke webservices witout usage of the custom index files jsonrpc.php and phpxmlrpc.php
    * Execution will be slower, but access control can be managaed via standard user policies
    */
    'execute' => array(
        'script' => 'execute.php',
        'params' => array( 'protocol' ),
        'functions' => array( 'proxy' ),
    ),

    'debugger' => array(
        'script' => 'debugger.php',
        //'params' => array( 'protocol' ),
        'functions' => array( 'proxy' ),
    ),
);

$FunctionList = array(
    'execute' = array(),
    'proxy' = array(),
	'debugger' = array()
);

?>
