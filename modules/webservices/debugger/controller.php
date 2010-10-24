<?php
/**
 * WS debugger: top frame
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright 2010
 */

include( dirname( __FILE__ ) . "/common.php" );

if ($action == '')
    $action = 'list';

/*// pass on control to actual debugger, setting some vars for it
$editorpath = '/extension/ggwebservices/design/';
eZURI::transformURI( $editorpath, true, 'full' );
$visualeditorpath = '../visualeditor';*/

require_once( "kernel/common/template.php" );
$tpl = templateInit();
/// @todo set vars to tpl
$Result['content'] = $tpl->fetch( "design:webservices/debugger/controller.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';

?>
