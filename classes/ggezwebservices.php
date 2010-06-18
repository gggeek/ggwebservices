<?php
/**
 * helper class, used as container for generic functions used throughout the extension
 *
 * @author G. Giunta
 * @version $Id: ggezwebservicesclient.php 102 2009-09-02 09:03:34Z gg $
 * @copyright (C) G. Giunta 2009
 */

class ggeZWebservices
{

    static $errorlevels = array(
        'info' => 6,
        'notice' => 5,
        'debug' => 4,
        'warning' => 3,
        'error' => 2,
        'critical' => 1,
        'none' => 0);

    static $debuglevel = -1;

    static $serverprotocols = array( 'soap', 'jsonrpc', 'xmlrpc' );

    static $protocolconfigs = array( 'soap' => 'soap.ini', 'jsonrpc' => 'wsproviders.ini', 'xmlrpc' => 'wsproviders.ini' );

    /**
     * Logs the string $logString to the logfile webdav.log
     * in the current log directory (usually var/log).
     * If logging is disabled, nothing is done.
     */
    static function appendLogEntry( $logString, $debuglevel )
    {
        if ( !self::isLoggingEnabled( $debuglevel ) )
            return false;

        $varDir = eZSys::varDirectory();

        $logDir = 'log';
        $logName = 'webservices.log';
        $fileName = $varDir . '/' . $logDir . '/' . $logName;
        if ( !file_exists( $varDir . '/' . $logDir ) )
        {
            //include_once( 'lib/ezfile/classes/ezdir.php' );
            eZDir::mkdir( $varDir . '/' . $logDir, 0775, true );
        }

        if ( $logFile = fopen( $fileName, 'a' ) )
        {
            $nowTime = date( "Y-m-d H:i:s : " );
            $text = $nowTime . $logString;
            /*if ( $label )
               $text .= ' [' . $label . ']';*/
            fwrite( $logFile, $text . "\n" );
            fclose( $logFile );
        }
    }

    /**
     * Return true if logging is enabled.
     */
    static function isLoggingEnabled( $debuglevel )
    {
        $logging =& self::$debuglevel;
        if ( $logging < 0 )
        {
            $logging = 0; // shall we init to 1 or 2 ?
            $ini = eZINI::instance( 'wsproviders.ini' );
            if ( $ini->hasvariable( 'GeneralSettings', 'Logging' ) )
            {
                $level = $ini->variable( 'GeneralSettings', 'Logging' );
                if ( array_key_exists( $level, self::$errorlevels ) )
                {
                    $logging = self::$errorlevels[$level];
                }
            }
            //ggeZWebservicesClient::$debuglevel = $logging;
        }
        if ( !array_key_exists( $debuglevel, self::$errorlevels ) )
        {
            return false;
        }
        return self::$errorlevels[$debuglevel] <= $logging;
    }

    /**
     * Function used for perms checking: list of defined ws servers
     */
    static function getServersList()
    {
        $target_list = array();
        $i = 0;
        $wsINI = eZINI::instance( 'wsproviders.ini' );
        // calculate list of target ws servers as it is hard to do that in tpl code
        foreach ( $wsINI->groups() as $groupname => $groupdef )
        {
            if ( $groupname != 'GeneralSettings' && $groupname != 'ExtensionSettings' )
            {
                if ( $wsINI->hasVariable( $groupname, 'providerType' ) )
                {
                    $target_list[] = array( 'name' => $groupname, 'id' => $groupname );
                }
                else
                {
                    /// @todo log warning ???
                }
            }
        }
        return $target_list;
    }

    /**
     * Function used for perms checking: list of defined ws methods.
     * NB: to get the list of functions, we have to include some php files that
     * define them. We hope that those files have no side effects, but it's left
     * up to the single coder's style...
     * @param string $protocol If null, functions for all supported protocols
     *        will be listed (soap, xmlrpc, jsonrpc)
     * @return array 2-level array, in the form needed by the permission system
     * @todo find a way to retain name/id association even if list of existing webservices change...
     */
    static function getMethodsList( $protocol=null )
    {
        $function_list = array();
        foreach( self::$serverprotocols as $serverprotocol )
        {
            if ( $protocol == null || $protocol == $serverprotocol )
            {
                $serverclass = 'gg' . strtoupper( $serverprotocol ) . 'Server';
                $server = new $serverclass();
                self::registerAvailableMethods( $server, $serverprotocol );
                $function_list = array_merge( $function_list, $server->registeredMethods() );
                if ( self::isRegisterAllProtocolsFunctionsEnabled() )
                {
                    // all methods will have already be registered regardless of protocol,
                    // so we can skip the rest of the loop
                    break;
                }
            }
        }
        $result = array();
        sort( $function_list );
        foreach( array_unique( $function_list ) as $key => $method )
        {
            $result[] = array( 'name' => $method, 'id' => $key );
        }
        return $result;
    }

    /**
    * Function used to register php code into a ws server
    * If isRegisterAllProtocolsFunctionsEnabled() == true, will register functions
    * declared for all protocols, regardless of tha value of $protocol, unless
    * $forceSingleProtocol is set to true
    * @param ggWebservicesServer $server
    * @param string $protocol if null php functions available for all protocols will be registered
    * @param bool $forceSingleProtocol overrides the ini parameter RegisterAllProtocolsFunctions
    */
    static function registerAvailableMethods( $server, $protocol=null, $forceSingleProtocol=false )
    {
        if ( self::isRegisterAllProtocolsFunctionsEnabled() && !$forceSingleProtocol )
        {
            $protocol = null;
        }
        foreach( self::$serverprotocols as $serverprotocol )
        {
            if ( $protocol == null || $protocol == $serverprotocol )
            {
                $wsINI = eZINI::instance( self::configFileByProtocol( $serverprotocol ) );
                foreach( $wsINI->variable( 'ExtensionSettings', strtoupper( $serverprotocol ) . 'Extensions' ) as $extension )
                {
                    include_once( eZExtension::baseDirectory() . '/' . $extension . '/' . $serverprotocol . '/initialize.php' );
                }
            }
        }
    }

    /**
    * @return bool
    */
    static function isRegisterAllProtocolsFunctionsEnabled()
    {
        $wsINI = eZINI::instance( 'wsproviders.ini' );
        return $wsINI->variable( 'GeneralSettings', 'RegisterAllProtocolsFunctions' ) == 'enabled';
    }

    /**
    * Used by the permission system: check if current user has access to ws method
    * @param string $methodName
    */
    static function checkAccess( $methodName )
    {
        $user = eZUser::currentUser();
        //$userID = $user->attribute( 'contentobject_id' );

        $accessResult = $user->hasAccessTo( 'webservices' , 'execute' );
        $accessWord = $accessResult['accessWord'];

        if ( $accessWord == 'yes' )
        {
            return 1;
        }
        elseif ( $accessWord == 'no' )
        {
            return 0;
        }
        else
        {
            $accessWs = false;
            $policies = $accessResult['policies'];
            foreach ( array_keys( $policies ) as $pkey  )
            {
                $limitationArray = $policies[ $pkey ];
                foreach ( $limitationArray as $key => $value  )
                {
                    switch( $key )
                    {
                        case 'Webservices':
                        {
                            if( $methodName === false || in_array( $methodName, $value ) )
                            {
                                $accessWs = true;
                            }

                        } break;
                    }
                }

                if ( $accessWs )
                {
                    return 1;
                }
            }

            return 0;
        }
    }

    /**
    * Returns the name of the ini file used to store info about a particular protocol.
    * This allows to have soap settings in soap.ini and settings for other
    * webservices in wsproviders.ini
    * NB: shall we return wsproviders.ini on not-found rather than null (= site.ini)?
    * @param string $protocol
    * @return string
    */
    static function configFileByProtocol( $protocol )
    {
        return isset( self::$protocolconfigs[$protocol] ) ? self::$protocolconfigs[$protocol] : null;
    }

}

?>