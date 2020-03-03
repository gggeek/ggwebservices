<?php
/**
 * This code is used to illustrate usage of the "ajax" functionality offered by
 * the extension - it provides the webservice which is accessed via javascript
 * embedded in a template.
 *
 * The native eZPublish "notification" functionality (which is by default
 * available as a view) gets here exposes as jsonrpc webservice.
 *
 * 1. this file should be copied/moved in extension/<myext>/jsonrpc/initialize.php
 * ( where "myext" is an extension which declares to expose jsonrpc webservices,
 *   by being set in wsproviders.ini, JSONRPCExtensions[] setting)
 * 2. proper access policies for webservices/execute should be set up
 * 3. the URL for accessing it is
 *    http://<my.server>/index.php/<siteaccess>/webservices/execute/jsonrpc
 */
$server->registerFunction(
    'notification.addtonotification', // name of websevice AND of the php function implementing it (with . replaced by _)
    array( 'ContentNodeID' => 'integer'  ),
    'integer',
    'Creates a notification for the given node for the current user. Returns 1 on creation, 2 if subscription already exists, or error' );

function notification_addtonotification( $nodeID )
{
    // code mostly stolen from notification/addtonotification view

    $user = eZUSer::currentUser();
    $contentNode = eZContentObjectTreeNode::fetch( $nodeID );
    if ( !$contentNode )
    {
        eZDebug::writeError( 'The nodeID parameter was empty or wrong, user ID: ' . $user->attribute( 'contentobject_id' ), __METHOD__ );
        throw new Exception( 'The nodeID parameter was empty or wrong', -10123 );
    }
    if ( !$contentNode->attribute( 'can_read' ) )
    {
        eZDebug::writeError( 'User does not have access to subscribe for notification, node ID: ' . $nodeID . ', user ID: ' . $user->attribute( 'contentobject_id' ), __METHOD__ );
        throw new Exception( 'User does not have access to subscribe for notification', -10234 );
    }

    $nodeIDList = eZSubtreeNotificationRule::fetchNodesForUserID( $user->attribute( 'contentobject_id' ), false );
    if ( !in_array( $nodeID, $nodeIDList ) )
    {
        $rule = eZSubtreeNotificationRule::create( $nodeID, $user->attribute( 'contentobject_id' ) );
        $rule->store();
        return 1;
    }

    // notification already exists
    return 2;
}
