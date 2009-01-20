<?php
/**
 * Ths jsonrpc.php file will handle all incoming JSON-RPC requests.
 * Code copied over from soap.php from eZP 4.0
 *
 * @author G. Giunta
 * @version $Id$
 */

ob_start();

ini_set( "display_errors" , "0" );

//require_once( "lib/ezutils/classes/ezdebug.php" );
//include_once( "lib/ezutils/classes/ezini.php" );
//include_once( 'lib/ezutils/classes/ezsys.php' );
//require_once( 'lib/ezutils/classes/ezexecution.php' );
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
eZSys::init( 'jsonrpc.php', $ini->variable( 'SiteAccessSettings', 'ForceVirtualHost' ) == 'true' );


// include ezsession override implementation
require_once( "lib/ezutils/classes/ezsession.php" );

// Check for extension
//include_once( 'lib/ezutils/classes/ezextension.php' );
require_once( 'kernel/common/ezincludefunctions.php' );
eZExtension::activateExtensions( 'default' );
// Extension check end


// Activate correct siteaccess
require_once( "access.php" );
$access = array( 'name' => $ini->variable( 'SiteSettings', 'DefaultAccess' ),
                 'type' => EZ_ACCESS_TYPE_DEFAULT );
$access = changeAccess( $access );
// Siteaccess activation end

// Check for activating Debug by user ID (Final checking. The first was in eZDebug::updateSettings())
eZDebug::checkDebugByUser();

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

// Load jsonrpc extensions
$wsINI = eZINI::instance( 'ws_providers.ini' );
$enable = $wsINI->variable( 'GeneralSettings', 'EnableJSONRPC' );

if ( $enable == 'true' )
{
    eZSys::init( 'jsonrpc.php' );

    //include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

    // Login if we have username and password.
    if ( eZHTTPTool::username() and eZHTTPTool::password() )
        eZUser::loginUser( eZHTTPTool::username(), eZHTTPTool::password() );

    //include_once( 'lib/ezsoap/classes/ezsoapserver.php' );

    $server = new ggJSONRPCServer();

    foreach( $wsINI->variable( 'ExtensionSettings', 'JSONRPCExtensions' ) as $extension )
    {
        include_once( eZExtension::baseDirectory() . '/' . $extension . '/jsonrpc/initialize.php' );
    }

    $server->processRequest();
}

ob_end_flush();

eZExecution::cleanExit();

?>
