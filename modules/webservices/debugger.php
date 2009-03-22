<?php
/**
 *
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright 2009
 *
 * @bug for xmlrpc, datetime and base64 parameters will be sent to remote server as strings...
 */

// decode input params
$target = $Params['target'];
$patchtarget = $Params['patchtarget'];
$query_string = '';

if ( $target != 'action' && $target != 'controller' && $target != 'visualeditor' )
{
    // display the iframe_based template
    require_once( "kernel/common/template.php" );
    $tpl = templateInit();
    $tpl->setVariable( 'query_string', $query_string );
    $Result = array();
    $Result['content'] = $tpl->fetch( "design:webservices/debugger.tpl" );
    $Result['left_menu'] = 'design:parts/wsdebugger/menu.tpl';
    $Result['path'] = array( array( 'url' => 'webservices/debugger',
                                    'text' => ezi18n( 'extension/webservices', 'WS Debugger' ) ) );
}
else
{
    // pass on control to actual debugger, setting some vars for it
    $editorpath = '/extension/ggwebservices/design/admin/debugger/';
    eZURI::transformURI( $editorpath, true, 'full' );
    $visualeditorpath = '../visualeditor';
    // fix weird behaviour with iframes, relative urls
    if ( $patchtarget == 'action' || $patchtarget == 'controller' )
    {
        $target = $patchtarget;
    }

    ini_set( 'display_errors', 0 );
    include( dirname( __FILE__ ) . "/debugger/$target.php" );
    eZExecution::cleanExit();
}

?>