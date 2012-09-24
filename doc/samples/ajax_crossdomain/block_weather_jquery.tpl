{*
 * This template illustrates usage of the "proxy" functionality offered by the
 * extension.
 *
 * It does so by forwarding requests to the Weather Underground webservices API.
 *
 * Instructions:
 * - sign up for an api key with Weather Underground
 * - edit wsproviders.ini.append.php and add the key in the url for the "wunderground" server
 * - make sure all eZPublish users who will access this template have a policy
 *   giving them the rights to access webservices/proxy
 *}

<a id="goj" href="#">Test Using JQuery</a>
{* note: the following line can be replaced with an ezscript_require and placed in html page's head *}
{ezscript_load( array( 'ezjsc::jquery', 'ggwstemplate::jquery::json', 'ggwstemplate::jquery::jsonrpc' ) )}
<script type="text/javascript">
{literal}
$( document ).ready( function(){
   jQuery.wsproxy(
       'wunderground',
       'forecast/q/Norway/Oslo.json',
       [],
       {
           success: function( r ){
               alert( r.responseJSON.content.forecast.txt_forecast.date );
           },
           error: function( r ){
               alert( r.statusText );
           }
       }
   );
});
{/literal}
</script>

<span>Oslo</span> forecast: <img id="weatherimg" src=""/> <span id="forecast"></span>
