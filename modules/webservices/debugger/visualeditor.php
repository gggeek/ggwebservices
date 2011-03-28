<?php
/**
 * WS debugger: Dialog for visually editing trees of json/xmlrpc values
 *
 * @version $Id$
 * @copyright (C) 2006-2011 G. Giunta
 * @author Gaetano Giunta
 *
 * @todo reimplement support for xmlrpc types which are outside of json (datetime, base64) ?
 *       nb: this is not very useful since it's not supported by the php xmlrpc client anyway
 *
 * @todo do not set to "null" new nodes
 * @todo add http no-cache headers. Is it really necessary? After all, a single http roundtrip is used...
 * @todo find a better way to preview large trees of values (at least make all panel draggable)
 * @todo improve display: do not show up/down arrows, 'angle line' for parameters, move up list numbers in ff
 */

// this line moved to the top for a small bit extra safety when no rewrite rules
// are in place
require_once( "kernel/common/template.php" );

$params = array();
$paramvals = array();
$type = 'xmlrpc';

// parse GET parameters and html-cleanse them

/// base64-encoded json structure
if ( isset( $_GET['params'] ) && $_GET['params'] != '' )
{
    //$params = explode(';', $_GET['params'] );
	$paramvals = json_decode( base64_decode( $_GET['params'] ), true );
	if ( json_last_error() != JSON_ERROR_NONE )
	{
		$paramvals = array();
	}
	else if ( !is_array( $paramvals ) )
	{
		$paramvals = array( $paramvals );
	}
}
foreach( $paramvals as $i => $pval )
{
	/// separate arrays from structs
	$ptype = strtolower( gettype( $pval ) );
	if ( $ptype == 'array' && array_keys( $pval ) != range( 0, count( $pval ) -1 ) )
	{
		$ptype = 'struct';
	}
	$params[$i] = array( 'value' => $pval, 'type' => $ptype );
}

if ( isset( $_GET['type'] ) && in_array( $_GET['type'], array( 'jsonrpc', 'xmlrpc', 'soap' ) ) )
{
	$type = $_GET['type'];
}
if ( $type == 'jsonrpc' )
{
    /// list of scalar types we accept as valid (struct, arrays are always ok)
    $valid_types = array( 'string', 'null', 'double', 'boolean' );
    // be kind when receiving a param specced as int: treat it as double
    foreach( $params as $key => $val )
    {
  	    if ( preg_match( '/^integer$/i', $val['type'] ) )
    	{
      	    $params[$key]['type'] = 'double';
      	}
    }
}
else
{
	foreach( $params as $key => $val )
	{
		if ( preg_match( '/^integer$/', $val['type'] ) )
		{
			$params[$key]['type'] = 'int';
		}
	}
    $valid_types = array( 'string', 'i4', 'int', 'double', 'boolean', 'base64', 'datetime.iso8601' );

	// always use jsonrpcvals for the visual editor
	$type = 'jsonrpc';
}

/// when set to true/1, adding new vals or modifying type of initial values is forbidden
$noadd = ( isset( $_GET['noadd'] ) ) ? (bool)$_GET['noadd'] : false;

$tpl = templateInit();
$tpl->setVariable( 'noadd', $noadd );
$tpl->setVariable( 'type', $type );
$tpl->setVariable( 'valid_types', $valid_types );
$tpl->setVariable( 'params', $params );
$Result['content'] = $tpl->fetch( "design:webservices/debugger/visualeditor.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';

?>
