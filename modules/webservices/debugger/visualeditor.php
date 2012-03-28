<?php
/**
 * WS debugger: Dialog for visually editing trees of values
 *
 * @copyright (C) 2006-2012 G. Giunta
 * @author Gaetano Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @todo reimplement support for xmlrpc types which are outside of json (datetime, base64) ?
 *       nb: this is not very useful if it's not supported by the php xmlrpc client anyway
 * @todo also in visual editor disallow types outside of the protocol range (eg. null for xmlrpc)
 */

// this line moved to the top for a small bit extra safety when no rewrite rules
// are in place
require_once( "kernel/common/template.php" );

$type = 'xmlrpc';
$named_params = false;
$paramsjson = '';
$paramscount = 0;

// parse GET parameters and html-cleanse them

if ( isset( $_GET['type'] ) && in_array( $_GET['type'], array( 'jsonrpc', 'xmlrpc', 'ezjscore', 'soap' ) ) )
{
    $type = $_GET['type'];
}

// we expect to receive a base64-encoded json structure
if ( isset( $_GET['params'] ) && $_GET['params'] != '' )
{
	$paramvals = json_decode( base64_decode( $_GET['params'] ), true );
    if ( function_exists( 'json_last_error' ) )
    {
        $err = json_last_error();
    }
    else
    {
        $err = ( $val === null ) ? 1 : false;
    }
	if ( $err )
	{
		$paramvals = array();
	}
	else if ( !is_array( $paramvals ) )
	{
		$paramvals = array( $paramvals );
	}
}
else
{
    $paramvals = array();
}

switch( $type )
{
    case 'soap':
    case 'ezjscore':
        /// list of scalar types we accept as valid (struct, arrays are always ok)
        //$valid_types = array( 'string', 'null', 'int', 'double', 'boolean' );
        $named_params = true;
        break;
    /*case 'jsonrpc':
        $valid_types = array( 'string', 'null', 'double', 'boolean' );
        break;
    default:
    	$valid_types = array( 'string', 'i4', 'int', 'double', 'boolean', 'base64', 'datetime.iso8601' );*/
}


$paramscount = count( $paramvals );
if ( $paramscount )
{
    $paramsjson = json_encode( $paramvals );
}
else
{
    $paramsjson = $named_params ? '{}' : '[]';
}

// always use jsonrpcvals for the visual editor
//$jstype = 'jsonrpc';

/// when set to true/1, adding new vals or modifying type of initial values is forbidden
//$noadd = ( isset( $_GET['noadd'] ) ) ? (bool)$_GET['noadd'] : false;

$tpl = templateInit();
//$tpl->setVariable( 'noadd', $noadd );
//$tpl->setVariable( 'type', $jstype );
//$tpl->setVariable( 'valid_types', $valid_types );
$tpl->setVariable( 'paramsjson', $paramsjson );
$tpl->setVariable( 'paramscount', $paramscount );
//$tpl->setVariable( 'named_params', $named_params );
$Result['content'] = $tpl->fetch( "design:webservices/debugger/visualeditor.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';

?>
