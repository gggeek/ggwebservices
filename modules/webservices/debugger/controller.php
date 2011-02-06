<?php
/**
 * WS debugger: top frame
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) 2010-2011 Gaetano Giunta
 *
 * @todo add support for more options, such as ntlm auth to proxy, or request charset encoding
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 */

// this line moved to the top for a small bit extra safety when no rewrite rules
// are in place
require_once( "kernel/common/template.php" );

include( dirname( __FILE__ ) . "/common.php" );

if ( $params['action'] == '' )
    $params['action'] = 'list';

$tpl = templateInit();
$tpl->setVariable( 'params', $params );
$Result['content'] = $tpl->fetch( "design:webservices/debugger/controller.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';

?>
