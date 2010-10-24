<?php
/**
 * WS debugger: Dialog for visually editing trees of json/xmlrpc values
 * @version $Id$
 * @copyright G. Giunta 2006-2010
 * @author Gaetano Giunta
 *
 * @todo do not set to "null" new nodes
 * @todo add http no-cache headers. Is it really necessary? After all, a single http roundtrip is used...
 * @todo find a better way to preview large trees of values (at least make all panel draggable)
 * @todo improve display: do not show up/down arrows, 'angle line' for parameters, move up list numbers in ff
 */

// parse GET parameters and html-cleanse them

/// semicolon-separated list of types for the starting parameters
/// (hint: the number of shown parameters depends on this)
if ( isset( $_GET['params'] ) && $_GET['params'] != '' )
{
    $params = explode(';', $_GET['params'] );
}
else
{
    $params = array();
}

/// choose between json and xmlrpc
if ( isset( $_GET['type'] ) && $_GET['type'] == 'jsonrpc' )
{
    $type = 'jsonrpc';
    /// list of scalar types we accept as valid (struct, arrays are always ok)
    $valid_types = array( 'string', 'null', 'double', 'boolean' );
    // be kind when receiving a param specced as int: treat it as double
    foreach( $params as $key => $val )
    {
  	    if ( preg_match( '/^(i4|int)$/i', $val ) )
    	{
      	    $params[$key] = 'double';
      	}
    }
}
else
{
    $type = 'xmlrpc';
    $valid_types = array( 'string', 'i4', 'int', 'double', 'boolean', 'base64', 'datetime.iso8601' );
}

/// when set to true/1, adding new vals or modifying type of initial values is forbidden
$noadd = ( isset( $_GET['noadd'] ) ) ? (bool)$_GET['noadd'] : false;

require_once( "kernel/common/template.php" );
$tpl = templateInit();
$tpl->setVariable( 'noadd', $noadd );
$tpl->setVariable( 'type', $type );
$tpl->setVariable( 'valid_types', $valid_types );
$tpl->setVariable( 'params', $params );
$Result['content'] = $tpl->fetch( "design:webservices/debugger/visualeditor.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';

?>
