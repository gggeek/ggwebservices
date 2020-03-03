<?php
/**
 *
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 */

$Module = array( 'name' => 'Webservices', "variable_params" => false );

$ViewList = array(

    /*
    * View used to proxy webservices calls to other wervices, overcoming the same-domain limitation.
    * General scheme: call a jsonrpc or xmlrpc webservice via a js function in your templates
    * pointing to /webservices/proxy/jsonrpc/remoteservername or /webservices/proxy/xmlrpc/remoteservername
    */
    'proxy' => array(
        'script' => 'proxy.php',
        'params' => array( 'protocol', 'remoteServerName', 'session' ),
        'functions' => array( 'proxy' ),
    ),

    /*
    * View used to invoke webservices witout usage of the custom index files jsonrpc.php and phpxmlrpc.php
    * Execution will be slower, but access control can be managed via standard user policies
    */
    'execute' => array(
        'script' => 'execute.php',
        'params' => array( 'protocol', 'session' ),
        'functions' => array( 'execute' ),
    ),

    /*
    * By default every user can see the wsdl corresponding to the services he can execute.
    * As an alternative, giving to user access to the 'webservices/wsdl' policy will allow him
    * to see the wsdl for all existing operations
    */
    'wsdl' => array(
        'script' => 'wsdl.php',
        'params' => array( 'webservice' ),
        'unordered_params' => array( 'view' => 'ViewMode' ),
        'functions' => array( 'execute' ),
    ),

    /// together with wsdl, we generate xsd for complex types
    'xsd' => array(
        'script' => 'xsd.php',
        'params' => array( 'webservice' ),
        //'unordered_params' => array( 'view' => 'ViewMode' ),
        'functions' => array( 'execute' ),
    ),

    'debugger' => array(
        'script' => 'debugger.php',
        'params' => array( 'target', 'patchtarget' ),
        'functions' => array( 'debugger' ),
        'default_navigation_part' => 'wsdebuggernavigationpart',
    ),
);

$FunctionList = array(
    'execute' => array(
        'Webservices' => array(
            'name'=> 'Webservices',
            'values'=> array(),
            'path' => '../extension/ggwebservices/classes/', // starts within 'kernel'...
            'file' => 'ggezwebservices.php',
            'class' => 'ggeZWebservices',
            'function' => 'getMethodsList',
            'parameter' => array() ),
        'SiteAccess' => array(
            'name'=> 'SiteAccess',
            'values'=> array(),
            'path' => 'classes/',
            'file' => 'ezsiteaccess.php',
            'class' => 'eZSiteAccess',
            'function' => 'siteAccessList',
            'parameter' => array()
            )
         ),
    'proxy' => array(
        'RemoteServers' => array(
            'name'=> 'RemoteServers',
            'values'=> array(),
            'path' => '../extension/ggwebservices/classes/',
            'file' => 'ggezwebservices.php',
            'class' => 'ggeZWebservices',
            'function' => 'getServersList',
            'parameter' => array() )
    ),
	'debugger' => array(),
	'wsdl' => array(),
);
