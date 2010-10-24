<?php
/**
 * WS debugger: top frame
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) 2010 Gaetano Giunta
 *
 * @todo add support for more options, such as ntlm auth to proxy, or request charset encoding
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 */

include( dirname( __FILE__ ) . "/common.php" );

if ( $params['action'] == '' )
    $params['action'] = 'list';

require_once( "kernel/common/template.php" );
$tpl = templateInit();
$tpl->setVariable( 'params', $params );
$Result['content'] = $tpl->fetch( "design:webservices/debugger/controller.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';

?>
