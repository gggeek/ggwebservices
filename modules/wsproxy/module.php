<?php
/**
 * Module used to proxy webservices calls to other wervices, overcoming the same-domain limitation.
 * General scheme: call a jsonrpc or xmlrpc webservice via a js function in your templates pointing to /wsproxy/jsonrpc or /wsproxy/xmlrpc
 * @version $Id$
 * @copyright (C) G. Giunta 2008
 */

$Module = array( 'name' => 'wsproxy', "variable_params" => true );

$ViewList = array(
    'jsonrpc' => array(
        'script' => 'jsonrpc.php',
        'params' => array( 'remoteServerName' ),
        'functions' => array( 'calljsonrpc' ),
    ),
    'xmlrpc' => array(
        'script' => 'xmlrpc.php',
        'params' => array( 'remoteServerName' ),
        'functions' => array( 'callxmlrpc' ),
    ),
    'rest' => array(
        'script' => 'rest.php',
        'params' => array( 'remoteServerName' ),
        'functions' => array( 'callrest' ),
    ),
    'soap' => array(
        'script' => 'soap.php',
        'params' => array( 'remoteServerName' ),
        'functions' => array( 'callsoap' ),
    )
);

$FunctionList = array(
    'calljsonrpc' = array(),
    'callxmlrpc' = array(),
    'callrest' = array(),
    'callsoap' = array(),
);

?>
