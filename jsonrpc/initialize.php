<?php
/**
 * Example initialize.php file for creating php functions exposed as jsonrpc methods
 */

/*
$server->registerFunction( 'fetchSyndicationFeedObjectList', // name of exposed webservice
                           array( 'feedID' => 'integer' ), // input params array. Keys are not really used, as param validation is positional. Use null instead of an array to avoid type validation
                           'array', // type of return value
                           'Returns a list of...' );

function fetchSyndicationFeedObjectList( $feedID )
{
    if ( $feedID <= 0 )
    {
         // return a protocol-level error
         return ggWebservicesFault( -1, 'Invalid feed ID: negative value' );
     }
     else
         return array ( 'a', 'b', 'c' );
}
*/

?>
