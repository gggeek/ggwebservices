<?php
/**
 * This file will handle all incoming WS requests, regardless of protocol.
 * Defaulting to xmlrpc, it can simply be included by different 'meta-controllers'
 * that just set up the WS_PROTOCOL constant.
 * Code copied over from soap.php from eZP 4.0
 *
 * @author G. Giunta
 * @copyright (C) 2009-2022 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 */

if ( !defined( 'WS_PROTOCOL' ) )
{
    define( 'WS_PROTOCOL', 'xmlrpc' );
}

ob_start();

ini_set( "display_errors" , "0" );

//require_once( "lib/ezutils/classes/ezdebug.php" );
//include_once( "lib/ezutils/classes/ezini.php" );
//include_once( 'lib/ezutils/classes/ezsys.php' );
//require_once( 'lib/ezutils/classes/ezexecution.php' );

// Set a default time zone if none is given. The time zone can be overridden
// in config.php or php.ini.
if ( !ini_get( "date.timezone" ) )
{
    date_default_timezone_set( "UTC" );
}

require 'autoload.php';

/**
 Reads settings from site.ini and passes them to eZDebug.
*/
function eZUpdateDebugSettings()
{
    $ini = eZINI::instance();

    list( $debugSettings['debug-enabled'], $debugSettings['debug-by-ip'], $debugSettings['debug-by-user'], $debugSettings['debug-ip-list'], $debugSettings['debug-user-list'] ) =
        $ini->variableMulti( 'DebugSettings', array( 'DebugOutput', 'DebugByIP', 'DebugByUser', 'DebugIPList', 'DebugUserIDList' ), array ( 'enabled', 'enabled', 'enabled' ) );
    eZDebug::updateSettings( $debugSettings );
}

$ini = eZINI::instance();

// Initialize/set the index file.
eZSys::init( WS_PROTOCOL . '.php', $ini->variable( 'SiteAccessSettings', 'ForceVirtualHost' ) == 'true' );
$uri = eZURI::instance( eZSys::requestURI() );
$GLOBALS['eZRequestedURI'] = $uri;

// include ezsession override implementation
require_once( "lib/ezutils/classes/ezsession.php" );

// Check for extension
//include_once( 'lib/ezutils/classes/ezextension.php' );
require_once( 'kernel/common/ezincludefunctions.php' );
eZExtension::activateExtensions( 'default' );
// Extension check end

// Activate correct siteaccess
require_once( 'access.php' );
$wsINI = eZINI::instance( ggeZWebservices::configFileByProtocol( WS_PROTOCOL ) );
if ( $wsINI->variable( 'GeneralSettings', 'UseDefaultAccess' ) === 'enabled' )
{
    $access = array( 'name' => $ini->variable( 'SiteSettings', 'DefaultAccess' ),
                     'type' => EZ_ACCESS_TYPE_DEFAULT );
}
else
{
    $access = accessType( $uri,
                          eZSys::hostname(),
                          eZSys::serverPort(),
                          eZSys::indexFile() );
}
$access = changeAccess( $access );
// Siteaccess activation end

// reload wsproviders ini file, as there might be per-siteaccess settings
$wsINI->loadCache();

// Check for activating Debug by user ID (Final checking. The first was in eZDebug::updateSettings())
eZDebug::checkDebugByUser();

// Check for siteaccess extension
eZExtension::activateExtensions( 'access' );
// Siteaccess extension check end

/**
 Reads settings from i18n.ini and passes them to eZTextCodec.
*/
function eZUpdateTextCodecSettings()
{
    $ini = eZINI::instance( 'i18n.ini' );

    list( $i18nSettings['internal-charset'], $i18nSettings['http-charset'], $i18nSettings['mbstring-extension'] ) =
        $ini->variableMulti( 'CharacterSettings', array( 'Charset', 'HTTPCharset', 'MBStringExtension' ), array( false, false, 'enabled' ) );

    //include_once( 'lib/ezi18n/classes/eztextcodec.php' );
    eZTextCodec::updateSettings( $i18nSettings );
}

// Initialize text codec settings
eZUpdateTextCodecSettings();

//include_once( 'lib/ezdb/classes/ezdb.php' );
//$db = eZDB::instance();

// Initialize module loading
//include_once( "lib/ezutils/classes/ezmodule.php" );
$moduleRepositories = eZModule::activeModuleRepositories();
eZModule::setGlobalPathList( $moduleRepositories );

// Load extensions
$enable = $wsINI->variable( 'GeneralSettings', 'Enable' . strtoupper( WS_PROTOCOL ) );

if ( $enable == 'true' )
{
    eZSys::init( WS_PROTOCOL . '.php' );

    //include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

    // Login if we have username and password.
    if ( eZHTTPTool::username() and eZHTTPTool::password() )
        eZUser::loginUser( eZHTTPTool::username(), eZHTTPTool::password() );

    //include_once( 'lib/ezsoap/classes/ezsoapserver.php' );

    $server_class = 'gg' . strtoupper( WS_PROTOCOL ) . 'Server';
    $server = new $server_class();

    // nb: this will register methods declared only for $protocol or for all
    //     protocols, depending on ini settings
    ggeZWebservices::registerAvailableMethods( $server, WS_PROTOCOL );

    $server->processRequest();
}

ob_end_flush();

eZExecution::cleanExit();
