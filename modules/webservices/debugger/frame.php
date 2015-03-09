<?php
/**
 * WS debugger: external frame
 *
 * @author G. Giunta
 * @copyright (C) 2010-2015 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 */

//$query_string = '';

$wsINI = eZINI::instance( 'wsproviders.ini' );

// calculate params for local server, for consistency with what we do below
foreach ( array( 'xmlrpc', 'jsonrpc', 'ezjscore', 'soap', 'rest v1', 'rest v2' ) as  $i => $protocol ) // soap has to be last!
{
    $method = '';
    if ( $protocol == 'ezjscore' )
    {
        $uri = "ezjscore/call";
    }
	elseif ( $protocol == 'soap' )
	{
		$wsINI = eZINI::instance( 'soap.ini' );
		$uri = "webservices/wsdl";
	}
    else if ( $protocol == 'rest v1' )
    {
        $uri = "api/ezp/v1";
        $restv1 = in_array( 'ezprestapiprovider', eZExtension::activeExtensions() );
        $method = '/';
    }
    else if ( $protocol == 'rest v2' )
    {
        $uri = "api/ezp/v2";
        $ver = eZPublishSDK::majorVersion();
        $restv2 = ( $ver >= 2012 & eZPublishSDK::minorVersion() >= 9 ) || ( $ver >= 5 && $ver < 2011 ) ;
        $method = '/';
    }
    else
    {
        $uri = "webservices/execute/$protocol";
    }
    eZURI::transformURI( $uri , false, 'full' );

    if ( $protocol == 'rest v2' || $protocol == 'rest v1' )
    {
        // for now, manually remove siteaccess name if found in url
        $sa = $GLOBALS['eZCurrentAccess']['name'];
        $uri = str_replace( "/$sa/", "/", $uri );
    }

    /// @todo disable link if ezjscore not active, enable rest v1 and rest v2 ...
    if ( ( $protocol == 'ezjscore' && in_array( 'ezjscore', eZExtension::activeExtensions() ) ) ||
         ( $protocol != 'ezjscore' && $protocol != 'rest v1' && $protocol != 'rest v2' && $wsINI->variable( 'GeneralSettings', 'Enable' . strtoupper( $protocol ) ) == 'true' ) ||
         ( $protocol == 'rest v1' && $restv1 ) ||
         ( $protocol == 'rest v2' && $restv2 )
       )
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
        if ( $i > 4 )
            $i = 4;
        $params .= "&wstype=$i";
        /// @todo filter out all cookies except the one for current session ?
        $ccookies = array();
        foreach ( $_COOKIE as $cn => $cv )
        {
            $ccookies[] = $cn.urlencode('=').$cv;
        }
        $params .= '&clientcookies=' . implode( ', ', $ccookies );
        if ( $method != '' )
        {
            $params .= '&wsmethod=' . urlencode( $method );
        }
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
                    if ( isset( $target_list[$groupname]['Options']['accept'] ) )
                    {
                        $params .= '&accept=' . urlencode( $target_list[$groupname]['Options']['accept'] );
                    }
                    if ( isset( $target_list[$groupname]['Options']['requestHeaders'] ) )
                    {
                        $params .= '&extraheaders=' . urlencode( $target_list[$groupname]['Options']['requestHeaders'] );
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
$tpl = ggeZWebservices::eZTemplateFactory();
//$tpl->setVariable( 'query_string', $query_string );
$tpl->setVariable( 'target_list', $target_list );
$tpl->setVariable( 'server_list', $server_list );
$Result = array();
$Result['content'] = $tpl->fetch( "design:webservices/debugger/frame.tpl" );
$Result['left_menu'] = 'design:parts/wsdebugger/menu.tpl';
$Result['path'] = array( array( 'url' => 'webservices/debugger',
                                'text' => ggeZWebservices::ezpI18ntr( 'extension/webservices', 'WS Debugger' ) ) );

?>
