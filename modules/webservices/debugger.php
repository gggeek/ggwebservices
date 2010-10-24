<?php
/**
 * Main view for the WS module
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright 2010
 */

// decode input params
$target = $Params['target'];
$patchtarget = $Params['patchtarget'];
//$query_string = '';

if ( $target != 'action' && $target != 'controller' && $target != 'visualeditor' )
{
    $target = 'frame';
}

// fix weird behaviour with iframes, relative urls, when action reloads controller
/// @todo test: still needed?
if ( $patchtarget == 'action' || $patchtarget == 'controller' )
{
    $target = $patchtarget;
}

/// @todo come back to a subdir_based structure
include( dirname( __FILE__ ) . "/debugger/$target.php" );

?>