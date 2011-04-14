<?php
/**
 * View that shows the wsdl used for receiving soap calls
 *
 * @author G. Giunta
 * @copyright 2009-2011
 *
 * @todo add support for WSDL 2.0
 *
 * @todo support showing a single wsdl file for many methods, when the user
 *       created different wsdl files on its own (merge them somehow)
 */

// decode input params

$module = $Params['Module'];
$ws = $Params['webservice'];
$output_type = ( $Params['ViewMode'] == 'html' ? 'html' : 'wsdl' );

// check if soap is enabled
$wsINI = eZINI::instance( ggeZWebservices::configFileByProtocol( 'soap' ) );
if ( $wsINI->variable( 'GeneralSettings', 'EnableSOAP' ) == 'true' )
{
    //$namespaceURI = '';
    $server = new ggPhpSOAPServer();

    // nb: this will register methods declared only for soap or for all
    //     protocols, depending on ini settings
    ggeZWebservices::registerAvailableMethods( $server, 'soap' );

    /// @todo register ezjscore methods (hard to do...)

    // check perms: only show wsdl for methods user has access to
    $user = eZUser::currentUser();
    $accessResult = $user->hasAccessTo( 'webservices' , 'execute' );
    $accessWord = $accessResult['accessWord'];
    $methods = false;
    if ( $accessWord == 'yes' )
    {
        $methods = $ws;
    }
    else if ( $accessWord != 'no' ) // with limitation
    {
        foreach ( $accessResult['policies'] as $key => $policy )
        {
            if ( isset( $policy['Webservices'] ) )
            {
                if ( $ws != false && in_array( $ws, $policy['Webservices'] ) )
                {
                    // if user wants the wsdl for a single ws, check if it can be executed
                    $methods = $policy['Webservices'];
                    break;
                }
                else
                {
                    // if user wants global wsdl, only show him methods he can access
                    $methods[] = $policy['Webservices'];
                }

            }
        }
    }

    if ( $methods === false )
    {
        // Error access denied - shall we show an error response in protocol format instead of html?
        return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
    }

    // $method can be NULL (all methods), an array of methods or a single one
    // make it more homogeneous
    if ( $methods == null )
    {
        $methods = $server->registeredMethods();
    }
    else if ( is_string( $methods ) )
    {
        // verify that $ws is a valid webservice, as we did not check above
        if ( !in_array( $methods, $server->registeredMethods() ) )
        {
            return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
        }
        $methods = array( $methods );
    }

    $cachedir = eZSys::cacheDirectory() . '/webservices';
    $cachefile = eZClusterFileHandler::instance( $cachedir . '/' . md5( "$output_type," . implode( ',', $methods ) ) );
    if ( $cachefile->exists() )
    {
        $wsdl = $cachefile->fetchContents();
    }
    else
    {
        $wsdl_strings = array();
        $wsdl_functions = array();
        foreach ( $methods as $method )
        {
            $wsdl = $server->methodWsdl( $method );
            if ( $wsdl != null )
            {
                $wsdl_strings[$method] = $wsdl;
            }
            else
            {
                $sigs = $server->methodSignatures( $method );
                $wsdl_functions[$method] = array(
                    //'name' => $function,
                    'params' => $sigs[0]['in'],
                    'returntype' => $sigs[0]['out'],
                    'documentation' => $server->methodDescription( $method )
                );
            }
        }

        // wsdl building is done via template
        include_once( 'kernel/common/template.php' );
        $tpl = templateInit();

        // allow end user to register wsdl as filesystem files (or just complete wsdl)
        foreach( $wsdl_strings as $method => $wsdl )
        {
            if ( strpos( $wsdl, 'design:' ) === 0 || strpos( $wsdl, 'file:' ) === 0 )
            {
                $wsdl_strings[$method] = $tpl->fetch( $wsdl );
            }
        }

        if ( count( $wsdl_strings ) )
        {
            if ( count( $methods ) == 1 )
            {
                $wsdl = reset( $wsdl_strings );
            }
            else
            {
                /// @todo: multiple wsdl files created by user
                $wsdl = 'NOT SUPPORTED YET...';
            }
        }
        else
        {
            /// @todo !important we could build directly html output using an html template, to reduce resource usage
            $tpl->setVariable( 'wsname', $ws );
            /// @todo we should suse different service names if, depending on permissions, user cannot see all methods...
            $tpl->setVariable( 'servicename', $ws == '' ? 'SOAPWeb' : ucfirst( $ws ) );
            $tpl->setVariable( 'functions', $wsdl_functions );
            $wsdl = $tpl->fetch( "design:webservices/wsdl1.tpl" );
        }

        if ( $output_type == 'html' )
        {
            $xmlDoc = new DOMDocument();
            $xmlDoc->loadXML( $wsdl );

            $xslDoc = new DOMDocument();
            $xslDoc->load( './extension/ggwebservices/design/standard/stylesheets/debugger/wsdl-viewer.xsl' );

            $proc = new XSLTProcessor();
            $proc->importStylesheet( $xslDoc );
            $wsdl = $proc->transformToXML( $xmlDoc );
        }

        if ( strlen( $wsdl ) )
        {
            $cachefile->storeContents( $wsdl );
        }

    }

    if ( $output_type != 'html' )
    {
        //header( 'Content-type: application/wsdl+xml' );
    }

    echo $wsdl;

}
eZExecution::cleanExit();

?>