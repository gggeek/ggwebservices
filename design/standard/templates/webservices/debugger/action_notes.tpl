<h3>Instructions on usage of the debugger:</h3>
<ol>
    <li>Run a 'list available methods' action against desired server (for SOAP servers this needs a wsdl file)</li>
    <li>If list of methods appears, click on 'describe method' for desired method</li>
    <li>To run method: click on 'load method synopsis' for desired method. This will load a skeleton for method call parameters in the form above. Complete all values with appropriate data and click 'Execute'</li>
    <li>If you get any "call FAILED" error, use the "Show debug info" option to begin debugging</li>
</ol>
{if $curl|not()}
<p class="evidence">You will need to enable the CURL extension to use the HTTPS and HTTP 1.1 transports</p>
{/if}

<h3>Examples:</h3>
<p>
    Server Address: phpxmlrpc.sourceforge.net, Path: <a target="frmcontroller" href="./controller/?wsaction=&host=phpxmlrpc.sourceforge.net&path=/server.php&wstype=0">/server.php</a> (for xmlrpc)<br/>
    Server Address: www.webservicex.net, Path: <a target="frmcontroller" href="./controller/?wsaction=list&host=www.webservicex.net&path=geoipservice.asmx?WSDL&wstype=3&wsdl=1">/geoipservice.asmx?WSDL</a> (for soap with wsdl)<br/>
    Server Address: api.twitter.com, Path: /1, Method: <a target="frmcontroller" href="./controller/?wsaction=execute&host=api.twitter.com&path=/1&wsmethod=statuses/public_timeline.json&wstype=4">statuses/public_timeline.json</a> (for rest, json output)<br/>
    Server Address: where.yahooapis.com, Path: /, Method: <a target="frmcontroller" href="./controller/?wsaction=execute&host=where.yahooapis.com&path=/&wsmethod=geocode&wstype=4&methodpayload={ldelim}&quot;q&quot;:&quot;Klostergata 30, Skien&quot;,&quot;appid&quot;:&quot;[yourappidhere]&quot;{rdelim}">geocode</a>, Parameters: q=&quot;Klostergata 30, Skien&quot;, appid=[yourappidhere] (for rest, xml output)
</p>

<h3>Notes:</h3>
<ul>
    <li>The method calls are executed from the server (php code), not from the browser (javascript)</li>
    <li>Clicking on the left menu links will preload the address of this server itself for testing in the debugger. In this case the server will send a call to itself</li>
    <li>If you get an error <i>Fault code: [-301] Reason: 'Response received from server is not valid json/xmlrpc'</i> when testing the server itself, a probable cause is that you did neither specify a session cookie for your call, nor give rights to the anonymous user to execute webservice calls</li>
    <li>The format for cookies is to separate them using a comma</li>
    <li><b>The format for the payload is <a href="http://www.json.org/" target="_blank">json</a>, regardless of the webservice protocol in use</b></li>
    <li>For ezjscore calls, the GET parameters have to be specified after method name (eg: ezstarrating::rate::55::1::5). Parameters specified as part of Payload will be sent via the request body</li>
    <li>For REST calls:<ul>
            <li>The "method" name is appended to the URL by default. Use the "Name variable" option if you want method name passed in the query string</li>
            <li>When using GET, the Payload is serialized into the query string. For POST/PUT, it is serialized into the request body, using the format defined by the "Request type" option</li>
            <li><u>NB: You can specify custom formats for the request body, but it currently can only serialize to JSON, PHP, serialized PHP</u></li>
            <li>The "Response type" option is used to force proper parsing of response from servers that send incorrect Content-type headers. application/json and text/xml supported so far</li>
            <li>The format for extra http headers for the response is header=value, separated by commas</li>
            <li>The format for the request type can be any, as long as the library is able to encode the request using it. Most variations of json are accepted</li>
        </ul></li>
    <li>OAUTH authentication is not supported yet</li>
</ul>
