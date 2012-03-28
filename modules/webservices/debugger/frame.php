<?php
/**
 * WS debugger: external frame
 *
 * @author G. Giunta
 * @copyright (C) 2010-2012 Gaetano Giunta
 * @license code licensed under the GPL License: see LICENSE file
 */

// this line moved to the top for a small bit extra safety when no rewrite rules
// are in place
require_once( "kernel/common/template.php" );
include_once( 'kernel/common/i18n.php' );

//$query_string = '';

$wsINI = eZINI::instance( 'wsproviders.ini' );

// calculate params for local server, for consistency with what we do below
foreach ( array( 'xmlrpc', 'jsonrpc', 'ezjscore', 'soap' ) as  $i => $protocol ) // soap has to be last!
{
    if ( $protocol == 'ezjscore' )
    {
        $uri = "ezjscore/call";
    }
	elseif ( $protocol == 'soap' )
	{
		$wsINI = eZINI::instance( 'soap.ini' );
		$uri = "webservices/wsdl";
	}
    else
    {
        $uri = "webservices/execute/$protocol";
    }
    eZURI::transformURI( $uri , false, 'full' );
/// @todo disable link if ezjscore not active ...
    if ( ( $protocol == 'ezjscore' && in_array( 'ezjscore', eZExtension::activeExtensions() ) ) || ( $protocol != 'ezjscore' && $wsINI->variable( 'GeneralSettings', 'Enable' . strtoupper( $protocol ) ) == 'true' ) )
    {
        $url = parse_url( $uri );
        $params = '?wsaction=';
        $params .= '&host=' . $url['host'];
        $params .= '&port=' . ( isset( $url['port'] ) ? $url['port'] : '' );
        $params .= '&path=' . ( isset( $url['path'] ) ? $url['path'] : '/' );
        if ( $url['scheme'] == 'https' )
        {
            $params .= '&protocol=2';
        }
        $params .= "&wstype=$i";
        /// @todo filtyer out all cookies except the one for current session ?
        $ccookies = array();
        foreach ( $_COOKIE as $cn => $cv )
        {
            $ccookies[] = $cn.urlencode('=').$cv;
        }
        $params .= '&clientcookies=' . implode( ', ', $ccookies );
        $server_list[$protocol] = $params;
    }
    else
    {
        $server_list[$protocol] = '';
    }
}

$wsINI = eZINI::instance( 'wsproviders.ini' );

// calculate list of target ws servers as it is hard to do that in tpl code
/// @todo parse also all 'Options' that can be set per server
$target_list = array();
foreach ( $wsINI->groups() as $groupname => $groupdef )
{
    if ( $groupname != 'GeneralSettings' && $groupname != 'ExtensionSettings' )
    {
        if ( $wsINI->hasVariable( $groupname, 'providerType' ) )
        {
            $target_list[$groupname] = $groupdef;
            if ( $groupdef['providerUri'] == '' && @$groupdef['WSDL'] != '' )
            {
                $url = parse_url( $groupdef['WSDL'] );
                if ( !empty( $url['query'] ) )
                {
                    $url['path'] = $url['path'] . '?' . $url['query'];
                }
            }
            else
            {
                $url = parse_url( $groupdef['providerUri'] );
            }
            if ( !isset( $url['scheme'] ) || !isset( $url['host'] ) )
            {
                $target_list[$groupname]['providerType'] = 'FAULT';
            }
            else
            {
                $params = '?wsaction=';
                $params .= '&host=' . $url['host'];
                $params .= '&port=' . ( isset( $url['port'] ) ? $url['port'] : '' );
                $params .= '&path=' . ( isset( $url['path'] ) ? urlencode( $url['path'] ) : '/' );
                if ( $url['scheme'] == 'https' )
                {
                    $params .= '&protocol=2';
                }
                else if( @$target_list[$groupname]['Options']['authType'] > 1 )
                {
                    $params .= '&protocol=1';
                }
                if ( isset( $target_list[$groupname]['providerUsername'] ) && $target_list[$groupname]['providerUsername'] != '' )
                {
                    $params .= '&username=' . urlencode( $target_list[$groupname]['providerUsername'] ) . '&password=' . urlencode( $target_list[$groupname]['providerPassword'] );
                }
                if ( isset( $target_list[$groupname]['timeout'] ) )
                {
                    $params .= '&timeout=' . $target_list[$groupname]['timeout'];
                }
                if ( isset( $target_list[$groupname]['WSDL'] ) )
                {
                    $params .= '&wsdl=1';
                }
                else
                {
                    $params .= '&wsdl=0';
                }
                if ( isset( $target_list[$groupname]['Options'] ) )
                {
                    if ( isset( $target_list[$groupname]['Options']['login'] ) && $target_list[$groupname]['Options']['login'] != '' )
                    {
                        $params .= '&username=' . urlencode( $target_list[$groupname]['Options']['login'] ) . '&password=' . urlencode( $target_list[$groupname]['Options']['password'] );
                    }
                    if ( isset( $target_list[$groupname]['Options']['timeout'] ) )
                    {
                        $params .= '&timeout=' . $target_list[$groupname]['Options']['timeout'];
                    }
                    if ( isset( $target_list[$groupname]['Options']['authType'] ) )
                    {
                        $params .= '&authtype=' . $target_list[$groupname]['Options']['authType'];
                    }

                    if ( isset( $target_list[$groupname]['Options']['soapVersion'] ) )
                    {
                        $params .= '&soapversion=' . ( (int)$target_list[$groupname]['Options']['soapVersion'] - 1 );
                    }

                    if ( isset( $target_list[$groupname]['Options']['method'] ) )
                    {
                        $params .= '&verb=' . $target_list[$groupname]['Options']['method'];
                    }
                    if ( isset( $target_list[$groupname]['Options']['nameVariable'] ) )
                    {
                        $params .= '&namevariable=' . $target_list[$groupname]['Options']['nameVariable'];
                    }
                    if ( isset( $target_list[$groupname]['Options']['responseType'] ) )
                    {
                        $params .= '&responsetype=' . urlencode( $target_list[$groupname]['Options']['responseType'] );
                    }
                    if ( isset( $target_list[$groupname]['Options']['requestType'] ) )
                    {
                        $params .= '&requesttype=' . urlencode( $target_list[$groupname]['Options']['requestType'] );
                    }

                    /// @todo also parse from ini forceCURL, requestCompression, acceptedCompression
                }
                switch( $target_list[$groupname]['providerType'] )
                {
                    case 'JSONRPC':
                        $params .= '&wstype=1';
                        break;
                    case 'eZJSCore':
                        $params .= '&wstype=2';
                        break;
                    case 'PhpSOAP':
                        $params .= '&wstype=3';
                        break;
                    case 'REST':
                        $params .= '&wstype=4';
                        break;
                }
                $target_list[$groupname]['urlparams'] = $params;
            }
        }
    }
}
// display the iframe_based template
$tpl = templateInit();
//$tpl->setVariable( 'query_string', $query_string );
$tpl->setVariable( 'target_list', $target_list );
$tpl->setVariable( 'server_list', $server_list );
$Result = array();
$Result['content'] = $tpl->fetch( "design:webservices/debugger/frame.tpl" );
$Result['left_menu'] = 'design:parts/wsdebugger/menu.tpl';
$Result['path'] = array( array( 'url' => 'webservices/debugger',
                                'text' => ezi18n( 'extension/webservices', 'WS Debugger' ) ) );

?>