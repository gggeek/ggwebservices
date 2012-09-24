{*
 * This template illustrates usage of the "ajax" functionality offered by the
 * extension.
 *
 * The native eZPublish "notification" functionality (which is by default
 * available as a view) gets exposes as jsonrpc webservice, and this template
 * includes code to activate it. It should be included from a node template
 *}

<a id="goy" href="#">Be notified of changes to this page or children</a>
{ezscript_load( array( 'ezjsc::yui3', 'ggwstemplate::yui3::jsonrpc' ) )}
<script type="text/javascript">
{literal}
    YUI( YUI3_config ).use( 'event', 'io-jsonrpc', function( Y ){
        Y.one("#goy").on(
            'click',
            function( e )
            {
                Y.io.jsonrpc(
                    'notification.addtonotification',
                    [ {/literal}"{$node.node_id}a"{literal} ],
                    {
                        on:{
                            success: function( id, r ){
                                alert( "Result: " + r.responseJSON.content );
                            },
                            failure: function( id, r ){
                                alert( r.statusText );
                            }
                        }
                    }
                );
            }
        );
    });
{/literal}
</script>

