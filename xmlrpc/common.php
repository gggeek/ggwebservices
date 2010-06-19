<?php
/**
 * Webservices that can be registered for multiple protocols
 *
 * @todo move parts in common with webservices/execute.php to ggezwebservices
 * @todo decide if we need to check user access to current siteaccess
 */

$server->registerFunction( 'ezp.authandexec',
    array( 'username' => 'string','password' => 'string', 'method' => 'string', 'params' => 'array'  ),
    'mixed',
    'Executes the webservice $method with parameters $params after having logged in with $username/$password' );

// store a handle to current server for later reuse
$GLOBALS['ggws_server'] = $server;

function ezp_authandexec( $user, $password, $functionName, $params )
{
    $server = $GLOBALS['ggws_server'];

    // replicate here logic found in user/login
    $ini = eZINI::instance();
    if ( $ini->hasVariable( 'UserSettings', 'LoginHandler' ) )
    {
        $loginHandlers = $ini->variable( 'UserSettings', 'LoginHandler' );
    }
    else
    {
        $loginHandlers = array( 'standard' );
    }
    foreach ( $loginHandlers as $loginHandler )
    {
        $userClass = eZUserLoginHandler::instance( $loginHandler );
        $user = $userClass->loginUser( $user, $password );
        if ( $user instanceof eZUser )
        {
            // do we need to check this, really?
            //$hasAccessToSite = $user->canLoginToSiteAccess( $GLOBALS['eZCurrentAccess'] );
            //if ( $hasAccessToSite )
            //{
                // check if new user has access to the actual ws
                $accessResult = $user->hasAccessTo( 'webservices' , 'execute' );
                $accessWord = $accessResult['accessWord'];
                $access = false;
                if ( $accessWord == 'yes' )
                {
                    $access = true;
                }
                else if ( $accessWord != 'no' ) // with limitation
                {
                    //$policies = $accessResult['policies'];
                    foreach ( $accessResult['policies'] as $key => $policy )
                    {
                        if ( isset( $policy['Webservices'] ) && in_array( $functionName, $policy['Webservices'] ) )
                        {
                            $access = true;
                            break;
                        }
                    }
                }
                if ( !$access )
                {
                    return new ggWebservicesFault( ggWebservicesServer::INVALIDAUTHERROR, ggWebservicesServer::INVALIDAUTHSTRING );
                }

                if ( $server->isInternalRequest( $functionName ) )
                {
                    return $server->handleInternalRequest( $functionName, $params );
                }
                else
                {
                    return $server->handleRequest( $functionName, $params );
                }
            //}
            //else
            //{
            //    $user->logoutCurrent();
            //    // @todo ...
            //    //return $module->handleError( eZError::KERNEL_ACCESS_DENIED, 'kernel' );
            //    return new ggWebservicesFault( ggWebservicesServer::INVALIDAUTHERROR, ggWebservicesServer::INVALIDAUTHSTRING );
            //}
        }
    }

    return new ggWebservicesFault( ggWebservicesServer::INVALIDAUTHERROR, ggWebservicesServer::INVALIDAUTHSTRING );

}

?>