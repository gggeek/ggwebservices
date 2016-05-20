<?php
/**
 * helper class, used as container for generic functions used throughout the extension
 *
 * @author G. Giunta
 * @author Carlos Revillo
 * @copyright (C) 2009-2016 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
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

    static $serverprotocols = array( 'soap', 'jsonrpc', 'xmlrpc', 'rest' );

    static $protocolconfigs = array( 'soap' => 'soap.ini', 'jsonrpc' => 'wsproviders.ini', 'xmlrpc' => 'wsproviders.ini',  'rest' => 'wsproviders.ini' );

    /**
     * Logs the string $logString to the logfile webservices.log
     * in the current log directory (usually var/log).
     * If logging is disabled, nothing is done.
     *
     * In dev mode, also writes to the eZP logs to ease debugging (this happens
     * regardless of the logging level set for the extension itself)
     */
    static function appendLogEntry( $logString, $debuglevel )
    {
        $ini = eZINI::instance( 'site.ini' );
        if ( $ini->variable( 'DebugSettings', 'DebugOutput' ) == 'enabled' && $ini->variable( 'TemplateSettings', 'DevelopmentMode' ) == 'enabled' )
        {
            switch( $debuglevel )
            {
                case 'info':
                case 'notice':
                    eZDebug::writeNotice( $logString, 'ggwebservices' );
                    break;
                case 'debug':
                    eZDebug::writeDebug( $logString, 'ggwebservices' );
                    break;
                case 'warning':
                    eZDebug::writeWarning( $logString, 'ggwebservices' );
                    break;
                case 'error':
                case 'critical':
                    eZDebug::writeError( $logString, 'ggwebservices' );
                    break;

            }
        }

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
                    $target_list[] = array( 'name' => $groupname, 'id' => md5( $groupname ) );
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
                // hack: currently there is no 'soap' server, only phpsoap'
                if ( $serverclass == 'ggSOAPServer' )
                {
                    $serverclass = 'ggPhpSOAPServer';
                }
                $server = new $serverclass();
                self::registerAvailableMethods( $server, $serverprotocol );
                $function_list = array_merge( $function_list, $server->registeredMethods() );
                if ( self::isRegisterAllProtocolsFunctionsEnabled() )
                {
                    // all methods will have already been registered regardless of protocol,
                    // so we can skip the rest of the loop
                    break;
                }
            }
        }
        $result = array();
        sort( $function_list );
        foreach( array_unique( $function_list ) as $key => $method )
        {
            $result[] = array( 'name' => $method, 'id' => md5( $method ) );
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
    * Returns the wsdl file corresponding to a list of server's methods.
    * This is implemented here because
    * . it relies on the templating system
    * . it does its own caching
    * but it should really be a function of the server class itself.
    * Since we do not want to pollute server classes with eZ specifics, we chose
    * to avoid the reverse depenency by putting the code here.
    *
    * @todo avoid parameter creep: use an options array
    */
    static function methodsWSDL( $server, $methods, $service_name='', $return_url=false, $version=1, $output_type='wsdl', $external_typedefs=false )
    {
        $cachedir = eZSys::cacheDirectory() . '/webservices';
        $cachefilename = $cachedir . '/' . md5( "$output_type,$version," . implode( ',', $methods ) );
        $cachefile = eZClusterFileHandler::instance( $cachefilename );
        if ( $cachefile->exists() )
        {
            if ( $return_url )
            {
                $cachefile->fetch();
                return $cachefilename;
            }
            $wsdl = $cachefile->fetchContents();
        }
        else
        {
            $wsdl_strings = array();
            $wsdl_functions = array();
            foreach ( $methods as $method )
            {
                $wsdl = $server->userWsdl( $method );
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
            $tpl = self::eZTemplateFactory();

            // if the developer used custom wsdl files for his services, we do
            // not merge the files together, but create a wsdl file that links
            // to those single wsdl files. So we build the actual user-defined-wsdl
            // only in case it is the desired wsdl
            if ( count( $wsdl_strings ) && count( $methods ) == 1 )
            {
                $wsdl = reset( $wsdl_strings );
                // allow end user to register wsdl as filesystem files (or just complete wsdl)
                if ( strpos( $wsdl, 'design:' ) === 0 || strpos( $wsdl, 'file:' ) === 0 )
                {
                    $wsdl = $tpl->fetch( $wsdl );
                }
            }
            else
            {
                /// @todo !important we could build directly html output using an html template, to reduce resource usage
                $namespace = 'webservices/wsdl/' . $service_name;
                eZURI::transformURI( $namespace , false, 'full' );
                $tpl->setVariable( 'namespace', $namespace );
                /// @todo we should use different service names if, depending on permissions, user cannot see all methods...
                $tpl->setVariable( 'servicename', $service_name == '' ? 'SOAP' : ucfirst( $service_name ) );
                $tpl->setVariable( 'functions', $wsdl_functions );
                $tpl->setVariable( 'imports', array_keys( $wsdl_strings ) );
                $tpl->setVariable( 'externalxsd', $external_typedefs );
                $wsdl = $tpl->fetch( "design:webservices/wsdl{$version}.tpl" );
            }

            if ( strlen( $wsdl ) )
            {
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

                $cachefile->storeContents( $wsdl );
            }
            else
            {
                /// @todo if user wants HTML out, give him a en error page...

                $cachefilename = null; // used below as return value
            }
        }
        return $return_url ? $cachefilename : $wsdl;
    }

    /**
     * Returns the xml schema corresponding to a list of server's method's complex types.
     * This is implemented here because
     * . it relies on the templating system
     * . it does its own caching
     * but it should really be a function of the server class itself.
     * Since we do not want to pollute server classes with eZ specifics, we chose
     * to avoid the reverse depenency by putting the code here.
     */
    static function methodsXSD( $server, $methods, $service_name='', $return_url=false )
    {
        $cachedir = eZSys::cacheDirectory() . '/webservices';
        // make sure our cache file names do not collide with wsdl ones (see methodsWSDL)
        $cachefilename = $cachedir . '/' . md5( "xsd,1," . implode( ',', $methods ) );
        $cachefile = eZClusterFileHandler::instance( $cachefilename );
        if ( $cachefile->exists() )
        {
            if ( $return_url )
            {
                $cachefile->fetch();
                return $cachefilename;
            }
            $wsdl = $cachefile->fetchContents();
        }
        else
        {
            //$wsdl_strings = array();
            $wsdl_functions = array();
            foreach ( $methods as $method )
            {
                $wsdl = $server->userWsdl( $method );
                if ( $wsdl == null )
                {
                    $sigs = $server->methodSignatures( $method );
                    $wsdl_functions[$method] = array(
                        //'name' => $function,
                        'params' => $sigs[0]['in'],
                        'returntype' => $sigs[0]['out'],
                        //'documentation' => $server->methodDescription( $method )
                    );
                }
            }

            // wsdl building is done via template
            $tpl = self::eZTemplateFactory();

            /// @todo !important we could build directly html output using an html template, to reduce resource usage
            //$namespace = 'webservices/wsdl/' . $service_name . '/types';
            //eZURI::transformURI( $namespace , false, 'full' );
            //$tpl->setVariable( 'namespace', $namespace );
            /// @todo we should use different service names if, depending on permissions, user cannot see all methods...
            //$tpl->setVariable( 'servicename', $service_name == '' ? 'SOAP' : ucfirst( $service_name ) );
            $tpl->setVariable( 'functions', $wsdl_functions );
            $wsdl = $tpl->fetch( "design:webservices/xsd.tpl" );

            if ( strlen( $wsdl ) )
            {
                $cachefile->storeContents( $wsdl );
            }
            else
            {
                $cachefilename = null; // used below as return value
            }
        }
        return $return_url ? $cachefilename : $wsdl;
    }

    /**
     * Somehow inspired by ezPODocScanner::objInspect()
     * Lists the publicly accessible properties of a class as an array name => type
     * NB: returns php types, not xsd ones
     * @todo move to another class?
     */
    static function classInspect( $classname )
    {
        if ( !class_exists( $classname ) )
        {
            eZDebug::writeError( "Cannot introspect class $classname for wsdl generation: class not found", __METHOD__ );
            return array();
        }
        $classmethods = get_class_methods( $classname );
        if ( in_array( "attributes", $classmethods ) && in_array( "attribute", $classmethods ) )
        {
            // 'template object' (should be a descendant of ezpo)
            $out = array();
            if ( is_callable( 'ezPODocScanner::definition' ) )
            {
                // load theorical desc parsed from online docs. A warning is logged by ezPODocScanner if class is not found
                $defs = ezPODocScanner::definition( strtolower( $classname ) );
                if ( isset( $defs['attributes'] ) )
                {
                    foreach( $defs['attributes'] as $name => $attr )
                    {
                        if ( strpos( $attr['type'], 'object [' ) === 0 )
                        {
                            $typename = substr( $attr['type'], 8, -1 );
                            // currently we get lowercase class names, but we want correct casing
                            // (add strtolower in case in the future ezPODocScanner is fixed)
                            $typename = ezPODocScanner::findClassNameGivenLowerCaseName( strtolower( $typename ) );
                            $out[$name] = $typename;
                        }
                        else if ( strpos( $attr['type'], 'array [' ) === 0 )
                        {
                            $typename = substr( $attr['type'], 7, -1 );
                            if ( !ezPODocScanner::isscalar( $typename ) && $typename != 'array' )
                            {
                                $typename = ezPODocScanner::findClassNameGivenLowerCaseName( strtolower( $typename ) );
                            }
                            $out[$name] = 'array of ' . $typename;
                        }
                        else
                        {
                            $out[$name] = $attr['type'];
                        }
                    }
                }
            }
            else
            {
                eZDebug::writeError( "Cannot introspect subclass of eZPO for wsdl generation ($classname): missing extension ezpersistentobject_inspector", __METHOD__ );
            }
            return $out;
        }
        else
        {
            // not a template class: do a dump based on reflection
            $out = array();
            $reflectionClass = new ReflectionClass( $classname );
            foreach( $reflectionClass->getProperties( ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_STATIC ) as $prop )
            {
                $doc = $prop->getDocComment();
                $type = $doc == '' ? 'mixed' : self::typeFromDocComment( $doc );
                $out[$prop->name] = $type;
            }
            return $out;
        }
    }

    // @todo parse javadoc to find out type
    protected static function typeFromDocComment( $doc )
    {
        return 'mixed';
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
    * @param string $functionName
    * @param ezuser $user
    */
    static function checkAccess( $functionName, $user=null )
    {
        if ( $user == null )
        {
            $user = eZUser::currentUser();
        }

        $access = false;
        $accessResult = $user->hasAccessTo( 'webservices' , 'execute' );
        $accessWord = $accessResult['accessWord'];
        if ( $accessWord == 'yes' )
        {
            $access = true;
        }
        else if ( $accessWord != 'no' ) // with limitation
        {
            $currentsa = eZSys::ezcrc32( $GLOBALS['eZCurrentAccess']['name'] );
            $functionName = md5( $functionName );
            $accessws = 1;
            $accesssa = 1;
            foreach ( $accessResult['policies'] as $key => $policy )
            {
                if ( isset( $policy['Webservices'] ) && $accessws === 1 )
                {
                    $accessws = false;
                }
                if ( isset( $policy['Webservices'] ) && in_array( $functionName, $policy['Webservices'] ) )
                {
                    $accessws = true;
                }
                if ( isset( $policy['SiteAccess'] ) && $accesssa === 1 )
                {
                    $accesssa = false;
                }
                if ( isset( $policy['SiteAccess'] ) && in_array( $currentsa, $policy['SiteAccess'] ) )
                {
                    $accesssa = true;
                }
            }
            $access = $accessws && $accesssa;
        }

        return $access;
    }

    static function checkAccessToServer( $remoteserver, $user=null )
    {
        if ( $user == null )
        {
            $user = eZUser::currentUser();
        }
        $access = false;
        $accessResult = $user->hasAccessTo( 'webservices' , 'proxy' );
        $accessWord = $accessResult['accessWord'];
        if ( $accessWord == 'yes' )
        {
            $access = true;
        }
        else if ( $accessWord != 'no' ) // with limitation
        {
            $remoteserver = md5( $remoteserver );
            foreach ( $accessResult['policies'] as $key => $policy )
            {
                if ( isset( $policy['RemoteServers'] ) && in_array( $remoteserver, $policy['RemoteServers'] ) )
                {
                    $access = true;
                    break;
                }
            }
        }
        return $access;
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

    /**
     * Wrapper method to translate labels and eventually takes advantage of new 4.3 i18n API
     * @param $context
     * @param $message
     * @param $comment
     * @param $argument
     * @return string
     */
    public static function ezpI18ntr( $context, $message, $comment = null, $argument = null )
    {
        // eZ Publish < 4.3 => use old i18n system
        if( eZPublishSDK::majorVersion() == 4 && eZPublishSDK::minorVersion() < 3 )
        {
            include_once( 'kernel/common/i18n.php' );
            return ezi18n( $context, $message, $comment, $argument );
        }
        else
        {
            return ezpI18n::tr( $context, $message, $comment, $argument );
        }
    }

    /**
     * Wrapper method to initialize a template and eventually takes advantage of new 4.3 TPL API
     * @return eZTemplate
     */
    public static function eZTemplateFactory()
    {
        if( eZPublishSDK::majorVersion() == 4 && eZPublishSDK::minorVersion() < 3 )
        {
            include_once( 'kernel/common/template.php' );
            return templateInit();
        }
        else
        {
            return eZTemplate::factory();
        }
    }
}

?>
