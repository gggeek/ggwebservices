{*
 * This template illustrates usage of the "ajax" functionality offered by the
 * extension.
 *
 * The native eZPublish "notification" functionality (which is by default
 * available as a view) gets exposes as jsonrpc webservice, and this template
 * includes code to activate it. It should be included from a node template.
 *}

<a id="goj" href="#">Be notified of changes to this page or children</a>
{ezscript_load( array( 'ezjsc::jquery', 'ggwstemplate::jquery::json', 'ggwstemplate::jquery::jsonrpc' ) )}
<script type="text/javascript">
{literal}
   $("#goj").click( function(){
       jQuery.jsonrpc(
           'notification.addtonotification',
           [ {/literal}{$node.node_id}{literal} ],
           {
               success: function( r ){
                   alert( "Result: " + r.responseJSON.content );
               },
               error: function( r ){
                   alert( r.statusText );
               }
           }
       );
   });
{/literal}
</script>
