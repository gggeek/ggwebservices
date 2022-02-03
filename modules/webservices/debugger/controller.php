<?php
/**
 * WS debugger: top frame
 *
 * @author G. Giunta
 * @copyright (C) 2010-2022 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @todo add support for more options, such as ntlm auth to proxy, or request charset encoding
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 */

include( dirname( __FILE__ ) . "/common.php" );

if ( $params['action'] == '' )
    $params['action'] = 'list';

$tpl = ggeZWebservices::eZTemplateFactory();
$tpl->setVariable( 'params', $params );
$tpl->setVariable( 'known_req_content_types', ggRESTRequest::knownContentTypes() );
$tpl->setVariable( 'known_resp_content_types', ggRESTResponse::knownContentTypes() );
$Result['content'] = $tpl->fetch( "design:webservices/debugger/controller.tpl" );
$Result['pagelayout'] = 'debugger_pagelayout.tpl';
