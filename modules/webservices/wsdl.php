<?php
/**
 * View that shows the wsdl used for receiving soap calls
 *
 * @author G. Giunta
 * @copyright (C) 2011-2022 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @todo add support for letting user choose WSDL 2.0
 */

// decode input params

$module = $Params['Module'];
$ws = $Params['webservice'];
$output_type = ( $Params['ViewMode'] == 'html' ? 'html' : 'wsdl' );
$wsdl_version = 1;
$external_typedefs = false;

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
    $methods = false;
    $user = eZUser::currentUser();
    $accessResult = $user->hasAccessTo( 'webservices' , 'wsdl' );
    $accessWord = $accessResult['accessWord'];
    if ( $accessWord == 'yes' )
    {
        $methods = $ws;
    }
    else
    {
        $accessResult = $user->hasAccessTo( 'webservices' , 'execute' );
        $accessWord = $accessResult['accessWord'];
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

    $wsdl= ggeZWebservices::methodsWSDL( $server, $methods, $ws, false, $wsdl_version, $output_type, $external_typedefs );

    if ( $output_type != 'html' )
    {
        header( 'Content-type: application/wsdl+xml' );
    }

    echo $wsdl;

}
eZExecution::cleanExit();
