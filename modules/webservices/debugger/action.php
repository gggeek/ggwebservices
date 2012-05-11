<?php
/**
 * WS debugger: bottom frame
 *
 * @author Gaetano Giunta
 * @copyright (C) 2005-2012 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 *
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 * @todo use ob_start to catch debug info and echo it AFTER method call results?
 * @todo be smarter in creating client stub for proxy/auth cases: only set appropriate property of client obj
 *
 * @todo move to template-based output
 **/

include( dirname(__FILE__) . '/common.php' );


// Play it quick & dirty here: we are not going to rename all vars used in this
// php file until we have readied the version based on template usage.
// So we stick to the old convention of having many variables in scope
extract( $params );

if ( $action != 'inspect' || $debug )
{

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>XMLRPC Debugger</title>
  <meta name="robots" content="index,nofollow" />
<style type="text/css">
<!--
body {border-top: 1px solid gray; padding: 1em; font-family: Verdana, Arial, Helvetica; font-size: 8pt;}
h3 {font-size: 9.5pt;}
h2 {font-size: 12pt;}
.dbginfo {padding: 1em; background-color: #EEEEEE; border: 1px dashed silver; font-family: monospace; white-space: pre;}
#response {padding: 1em; margin-top: 1em; background-color: #DDDDDD; border: 1px solid gray; white-space: pre; font-family: monospace;}
table {padding: 2px; margin-top: 1em;}
th {background-color: navy; color: white; padding: 0.5em;}
td {padding: 0.5em; font-family: monospace;}
td form {margin: 0;}
.oddrow {background-color: #EEEEEE;}
.evidence {color: blue;}
#phpcode { background-color: #EEEEEE; padding: 1em; margin-top: 1em;}
-->
</style>
</head>
<body>
<?php

}

if ( $action )
{

    // make sure the script waits long enough for the call to complete
    if ( $timeout )
        set_time_limit( $timeout + 10 );

    // set up the ws client

    $methodseparator = '.';
    switch( $wstype )
    {
        case 4:
            $wsprotocol = 'REST';
            break;
        case 3:
            $wsprotocol = 'PhpSOAP';
            break;
        case 2:
            $wsprotocol = 'eZJSCore';
            $methodseparator = '::';
            break;
        case 1:
            $wsprotocol = 'JSONRPC';
            break;
        default:
            $wsprotocol = 'XMLRPC';
    }
    if ( $action == 'inspect' )
    {
        $wsprotocol = 'HTTP'; // override ws type: used to GET wsdl file
    }
    $clientClass = 'gg' . $wsprotocol . 'Client';
    $requestClass = 'gg' . $wsprotocol . 'Request';

    if ( $path == '' )
    {
        $path = '/';
    }
    if ( $port == '' )
    {
        if ( $protocol == 2 )
        {
            $port = 443;
        }
        else
        {
            $port = 80;
        }
    }
    $sport = ':' . $port;
    if ( $protocol == 2 ) // https
    {
        $sprotocol = 'https';
        if ( $port == 443 )
        {
            $sport = '';
        }
    }
    else // http
    {
        $sprotocol = 'http';
        if ( $port == 80 )
        {
            $sport = '';
        }
    }
    $server = $sprotocol . '://' . $host . $sport . $path;

    if ( $wsdl && $action != 'inspect' )
    {
        $client = new $clientClass( '', '', 0, '', $server );
        $server .= ' (for wsdl)';
    }
    else
    {
        $client = new $clientClass( $host, $path, $port, $sprotocol );
    }

    if ( $proxy != '' )
    {
        $pproxy = explode( ':', $proxy );
        if ( count( $pproxy ) > 1 )
            $pport = $pproxy[1];
        else
            $pport = 8080;
        $client->setOptions( array( 'proxyHost' => $pproxy[0], 'proxyPort' => $pport, 'proxyUser' => $proxyuser, 'proxyPassword' => $proxypwd ) );
    }

    if ( $timeout > 0 )
    {
        $client->setOption( 'timeout', $timeout );
    }

    if ( $protocol == 2 ) // https
    {
        $client->setOption( 'SSLVerifyPeer', $verifypeer );
        $client->setOption( 'SSLVerifyHost', $verifyhost );
        if ( $cainfo )
        {
            $client->setOption( 'SSLCAInfo', $cainfo );
        }
    }
    else if ( $protocol == 1 ) // http 1.1
    {
        $client->setOption( 'forceCURL', true );
    }

    if ( $username )
    {
        $client->setOptions( array( 'login' => $username, 'password' => $password, 'authType' => $authtype ) );
    }

    $client->setOption( 'debug', $debug );

    switch ( $requestcompression )
    {
        case 0:
            $client->setOption( 'requestCompression', '' );
            break;
        case 1:
            $client->setOption( 'requestCompression', 'gzip' );
            break;
        case 2:
            $client->setOption( 'requestCompression', 'deflate' );
            break;
    }

    switch ( $responsecompression )
    {
        case 0:
            $client->setOption( 'acceptedCompression', '' );
            break;
        case 1:
            $client->setOption( 'acceptedCompression', 'gzip' );
            break;
        case 2:
            $client->setOption( 'acceptedCompression', 'deflate' );
            break;
        case 3:
            $client->setOption( 'acceptedCompression', 'gzip, deflate' );
            break;
    }

    $cookies = explode( ',', $clientcookies );
    foreach ( $cookies as $cookie )
    {
        if (strpos($cookie, '=') )
        {
            $cookie = explode( '=', $cookie );
            $client->setCookie( trim( $cookie[0] ), trim( @$cookie[1] ) );
        }
    }

    if ( $soapversion == 1 )
    {
        $client->setOption( 'soapVersion', SOAP_1_2 );
    }

    if ( $wsprotocol == 'phpSOAP' )
    {
        $client->setOption( 'cacheWSDL', WSDL_CACHE_NONE );
    }

    if ( $wsprotocol == 'REST' )
    {
        if ( $verb != '' )
        {
            $client->setOption( 'method', $verb );
        }
        if ( $namevariable != '' )
        {
            $client->setOption( 'nameVariable', $namevariable );
        }
        if ( $responsetype != '' )
        {
            $client->setOption( 'responseType', $responsetype );
        }
        if ( $requesttype != '' )
        {
            $client->setOption( 'requestType', $requesttype );
        }
    }

    // prepare an array of ws calls to execute (can be one or two)

    $msg = array();
    switch ( $action )
    {
        case 'inspect':
            $msg[0] = new $requestClass( 'GET', array() );
            $actionname = 'WSDL inspection';
            break;

        case 'describe':
            if ( $wstype == 3 )
            {
                // for SOAP servers, the client only supports methodSignature
                $msg[0] = new $requestClass( 'system'.$methodseparator.'methodSignature', array( $method ), $id + 1 );
            }
            else if ($wstype == 2)
            {
                // no methodsig for ezjscore. methodHelp supported when ggws is installed on the server
                /// @todo to be verified: is methodhelp ok?
                $msg[0] = new $requestClass( 'system'.$methodseparator.'methodHelp'.$methodseparator.$method, array() );
            }
            else
            {
                // for jsonrpc, methodHelp and methodSignature are not standard methods:  ggws needs to be installed on the server
                $msg[0] = new $requestClass( 'system'.$methodseparator.'methodHelp', array( $method ), $id );
                $msg[1] = new $requestClass( 'system'.$methodseparator.'methodSignature', array( $method ), $id + 1 );
            }
            $actionname = 'Description of method "'.$method.'"';
            break;

        case 'list':
          	$msg[0] = new $requestClass( 'system'.$methodseparator.'listMethods', array(), $id );
            $actionname = 'List of available methods';
            break;

        case 'execute':

            $msg[0] = new $requestClass( $method, array(), $id );
            $actionname = 'Execution of method '.$method;
            if ( $payload != '' )
            {
                $php_payload = json_decode( $payload, true );
                if ( function_exists( 'json_last_error' ) )
                {
                    $err = json_last_error();
                }
                else
                {
                    $err = ( $val === null ) ? 1 : false;
                }
                if ( $err || !is_array( $php_payload ) )
                {
                    $actionname = '[ERROR: invalid payload (must be in json format)]';
                    $msg = array();
                }
                else
                {
                    $msg[0]->addParameters( $php_payload );
                }
            }
            break;

        default: // give a warning
            $actionname = '[ERROR: unknown action] "'.$action.'"';
    }

    // Before calling execute, echo out brief description of action taken + date and time ???
    // this gives good user feedback for long-running methods...
    /// @todo use a template for html layout
    if ( $action != 'inspect' || $debug )
    {
        echo '<h2>' . htmlspecialchars( $actionname ) . ' on server ' . htmlspecialchars( $server ) . " ...</h2>\n";
        flush();
    }

    // execute method(s)
	$response = null;
    $responses = array();
    $time = microtime( true );
    foreach ( $msg as $message )
    {
        $response = $client->send( $message );
        $responses[] = $response;
        if ( !is_object( $response ) || $response->isFault() )
            break;
    }
    $time = microtime( true ) - $time;

    if ( $debug )
    {
        /// @todo should echo the request+response of all requests, when sending more than 1
        echo '<div class="dbginfo"><h2>Debug info:</h2>';
        if ( $debug > 1 ) echo '<span class="evidence">Sent: </span>' . htmlspecialchars( $client->requestPayload() ) . "\n";
        echo '<span class="evidence">Received: </span>' . htmlspecialchars( $client->responsePayload() );
        if ( $debug > 1 && is_object( $response ) ) echo "\n" . '<span class="evidence">Cookies: </span>' . htmlspecialchars( var_export( $response->cookies(), true) ) ;
        echo "</div>\n";
    }

    if ( !is_object( $response ) )
    {
        // call failed! echo out error msg!
        echo "<h3>$wsprotocol call FAILED!</h3>\n";
        echo "<p>Fault code: [" . htmlspecialchars( $client->errorNumber() ) .
            "] Reason: '" . htmlspecialchars( $client->errorString() ) . "'</p>\n";
        echo ( strftime( "%d/%b/%Y:%H:%M:%S\n" ) );
    }
    else if ( $response->isFault() )
    {
        // call failed! echo out error msg!
        //echo '<h2>'.htmlspecialchars($actionname).' on server '.htmlspecialchars($server).'</h2>';
        echo "<h3>$wsprotocol call FAILED!</h3>\n";
        echo "<p>Fault code: [" . htmlspecialchars( $response->faultCode() ) .
            "] Reason: '" . htmlspecialchars( $response->faultString() ) . "'</p>\n";
        echo ( strftime( "%d/%b/%Y:%H:%M:%S\n" ) );
    }
    else
    {
        // call(s) succeeded: parse and display results

        //echo '<h2>'.htmlspecialchars($actionname).' on server '.htmlspecialchars($server).'</h2>';
        if ( $action != 'inspect' || $debug )
        {
            printf ( "<h3>%s call". ( count( $responses ) > 1 ? 's' : '' ) ." OK (%.2f secs.)</h3>\n", $wsprotocol, $time );
            echo ( strftime( "%d/%b/%Y:%H:%M:%S\n" ) );
        }

        switch ( $action )
        {
            case 'inspect':
                if ( !$debug ) // in debug mode, we do not transform the wsdl, but only show debug info
                {
                    $xmlDoc = new DOMDocument();
                    $xmlDoc->loadXML( $response->value() );

                    $xslDoc = new DOMDocument();
                    $xslDoc->load( './extension/ggwebservices/design/standard/stylesheets/debugger/wsdl-viewer.xsl' );

                    $proc = new XSLTProcessor();
                    $proc->importStylesheet( $xslDoc );
                    $result = $proc->transformToXML( $xmlDoc );

                    echo $result;
                }
                break;

            case 'list':

                $v = $response->value();
                if ( is_array( $v ) && array_keys( $v ) == range( 0, count( $v ) -1 ) )
                {
          $max = count( $v );
          echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
          echo "<thead>\n<tr><th>Method ($max found)</th><th>Description</th></tr>\n</thead>\n<tbody>\n";
          for($i=0; $i < $max; $i++)
          {
            $rec = $v[$i];
            if ($i%2) $class=' class="oddrow"'; else $class = ' class="evenrow"';
            echo "<tr><td$class>".htmlspecialchars( $rec )."</td><td$class>";
            echo "<form action=\"../controller/\" method=\"get\" target=\"frmcontroller\">".
              "<input type=\"hidden\" name=\"host\" value=\"".htmlspecialchars($host)."\" />".
              "<input type=\"hidden\" name=\"port\" value=\"".htmlspecialchars($port)."\" />".
              "<input type=\"hidden\" name=\"path\" value=\"".htmlspecialchars($path)."\" />".
              "<input type=\"hidden\" name=\"id\" value=\"".htmlspecialchars($id)."\" />".
              "<input type=\"hidden\" name=\"debug\" value=\"$debug\" />".
              "<input type=\"hidden\" name=\"username\" value=\"".htmlspecialchars($username)."\" />".
              "<input type=\"hidden\" name=\"password\" value=\"".htmlspecialchars($password)."\" />".
              "<input type=\"hidden\" name=\"authtype\" value=\"$authtype\" />".
              "<input type=\"hidden\" name=\"verifyhost\" value=\"$verifyhost\" />".
              "<input type=\"hidden\" name=\"verifypeer\" value=\"$verifypeer\" />".
              "<input type=\"hidden\" name=\"cainfo\" value=\"".htmlspecialchars($cainfo)."\" />".
              "<input type=\"hidden\" name=\"proxy\" value=\"".htmlspecialchars($proxy)."\" />".
              "<input type=\"hidden\" name=\"proxyuser\" value=\"".htmlspecialchars($proxyuser)."\" />".
              "<input type=\"hidden\" name=\"proxypwd\" value=\"".htmlspecialchars($proxypwd)."\" />".
              "<input type=\"hidden\" name=\"responsecompression\" value=\"$responsecompression\" />".
              "<input type=\"hidden\" name=\"requestcompression\" value=\"$requestcompression\" />".
              "<input type=\"hidden\" name=\"clientcookies\" value=\"".htmlspecialchars($clientcookies)."\" />".
              "<input type=\"hidden\" name=\"protocol\" value=\"$protocol\" />".
              "<input type=\"hidden\" name=\"timeout\" value=\"$timeout\" />".
              "<input type=\"hidden\" name=\"wsmethod\" value=\"".htmlspecialchars( $rec )."\" />".
              "<input type=\"hidden\" name=\"wstype\" value=\"$wstype\" />".
              "<input type=\"hidden\" name=\"wsdl\" value=\"$wsdl\" />".
              "<input type=\"hidden\" name=\"soapversion\" value=\"$soapversion\" />".
              "<input type=\"hidden\" name=\"wsaction\" value=\"describe\" />".
              //"<input type=\"hidden\" name=\"run\" value=\"now\" />".
              "<input type=\"submit\" value=\"Describe\" /></form>";
            echo "</td>";

            echo("</tr>\n");
          }
          echo "</tbody>\n</table>";
        }
        else
        {
        	echo "<p>Unexpected response: " . htmlspecialchars( print_r( $v, true ) ) ."</p>";
        }
        break;

            case 'describe':
                $r1 = $responses[0]->value();
                if ( count( $responses ) > 1 )
                {
                    $r2 = $responses[1]->value();
                }
                else
                {
                    if ( $wstype == 3 )
                    {
        	            $r2 = $r1;
                      	$r1 = '';
                    }
                    else
                    {
                        $r2 = null;
                    }
                }

        echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
        echo "<thead>\n<tr><th>Method</th><th colspan=\"2\">".htmlspecialchars($method)."</th></tr>\n</thead>\n<tbody>\n";
        $desc = htmlspecialchars($r1);
        if ($desc == "")
          $desc = "-";
        echo "<tr><td class=\"evenrow\">Description</td><td colspan=\"2\" class=\"evenrow\">$desc</td></tr>\n";

        if ( !is_array( $r2) )
          echo "<tr><td class=\"oddrow\">Signature</td><td class=\"oddrow\">Unknown</td><td class=\"oddrow\">&nbsp;</td></tr>\n";
        else
        {
          for($i=0; $i < count( $r2 ); $i++)
          {
          	$p = array();
          	$payload="";
          	$alt_payload="";
            if ($i+1%2) $class=' class="oddrow"'; else $class = ' class="evenrow"';
            echo "<tr><td$class>Signature&nbsp;".($i+1)."</td><td$class>";
            $x = $r2[$i];
            if ( is_array( $x ) )
            {
              $ret = array_shift( $x );
              echo "<code>OUT:&nbsp;" . htmlspecialchars( $ret ) . "<br />IN: (";
              if ( count( $x ) > 0 )
              {
                $k = 0;
                foreach( $x as $key => $y )
                {
                  //$y = $x[$k];
                  if ( $wstype == 3 )
                  {
                      echo htmlspecialchars( $key ). ': ';
                  }
                  echo htmlspecialchars( $y );
                  /*if ($wstype != 1 && $wstype != 2  && $wstype != 3)
                  {
                    $payload = $payload . '<param><value><'.htmlspecialchars($y).'></'.htmlspecialchars($y)."></value></param>\n";
                  }
                  $alt_payload .= $y;*/
                  if ( $k < count( $x )-1 )
                  {
                    //$alt_payload .= ';';
                    echo ", ";
                  }
                	switch( strtolower( $y ) )
                	{
                		case 'array':
                			$p[] = '[ ]';
                			break;
                		case 'int':
                		case 'integer':
                		case 'i4':
                			$p[] = '1';
                			break;
                		case 'bool':
                		case 'boolean':
                			$p[] = 'true';
                			break;
                		case 'struct':
                			$p[] = '{ }';
                			break;
                		case 'null':
                			$p[] = 'null';
                			break;
                		/// @todo cases: double, base64, datetime.iso8601
                		default:
                			$p[] = '"..."';
                	}
                    $k++;
                }
              }
              echo ")</code>";
              $payload = '[' . implode( ",\n", $p ) . ']';
              $alt_payload = implode( ',', $x );
            }
            else
            {
              echo 'Unknown';
            }
            echo '</td>';

            echo "<td$class><form action=\"../controller/\" target=\"frmcontroller\" method=\"get\">".
            "<input type=\"hidden\" name=\"host\" value=\"".htmlspecialchars($host)."\" />".
            "<input type=\"hidden\" name=\"port\" value=\"".htmlspecialchars($port)."\" />".
            "<input type=\"hidden\" name=\"path\" value=\"".htmlspecialchars($path)."\" />".
            "<input type=\"hidden\" name=\"id\" value=\"".htmlspecialchars($id)."\" />".
            "<input type=\"hidden\" name=\"debug\" value=\"$debug\" />".
            "<input type=\"hidden\" name=\"username\" value=\"".htmlspecialchars($username)."\" />".
            "<input type=\"hidden\" name=\"password\" value=\"".htmlspecialchars($password)."\" />".
            "<input type=\"hidden\" name=\"authtype\" value=\"$authtype\" />".
            "<input type=\"hidden\" name=\"verifyhost\" value=\"$verifyhost\" />".
            "<input type=\"hidden\" name=\"verifypeer\" value=\"$verifypeer\" />".
            "<input type=\"hidden\" name=\"cainfo\" value=\"".htmlspecialchars($cainfo)."\" />".
            "<input type=\"hidden\" name=\"proxy\" value=\"".htmlspecialchars($proxy)."\" />".
            "<input type=\"hidden\" name=\"proxyuser\" value=\"".htmlspecialchars($proxyuser)."\" />".
            "<input type=\"hidden\" name=\"proxypwd\" value=\"".htmlspecialchars($proxypwd)."\" />".
            "<input type=\"hidden\" name=\"responsecompression\" value=\"$responsecompression\" />".
            "<input type=\"hidden\" name=\"requestcompression\" value=\"$requestcompression\" />".
            "<input type=\"hidden\" name=\"clientcookies\" value=\"".htmlspecialchars($clientcookies)."\" />".
            "<input type=\"hidden\" name=\"protocol\" value=\"$protocol\" />".
            "<input type=\"hidden\" name=\"timeout\" value=\"".htmlspecialchars($timeout)."\" />".
            "<input type=\"hidden\" name=\"wsmethod\" value=\"".htmlspecialchars($method)."\" />".
            "<input type=\"hidden\" name=\"methodpayload\" value=\"".htmlspecialchars($payload)."\" />".
            "<input type=\"hidden\" name=\"altmethodpayload\" value=\"".htmlspecialchars($alt_payload)."\" />".
            "<input type=\"hidden\" name=\"wstype\" value=\"$wstype\" />".
            "<input type=\"hidden\" name=\"wsdl\" value=\"$wsdl\" />".
            "<input type=\"hidden\" name=\"soapversion\" value=\"$soapversion\" />".
            "<input type=\"hidden\" name=\"wsaction\" value=\"execute\" />";
            if ($wstype != 1 && $wstype != 2)
              echo "<input type=\"submit\" value=\"Load method synopsis\" />";
            echo "</form></td>\n";

            echo "<td$class>";
          	echo "</td></tr>\n";

          } // loop on sigs
        } // loop on methods
        echo "</tbody>\n</table>";
        break;

            case 'execute':
                $note = "";
                $value =  $response->value();
                if ( $value instanceof ggSimpleTemplateXML )
                {
                    $note = "<u>NB</u>: actual response is an object of class 'ggSimpleTemplateXML', it is shown as an array for convenience\n\n";
                    $value = $value->toArray();
                }
                echo '<div id="response"><h2>Response:</h2>'. $note . htmlspecialchars( print_r( $value, true ) ).'</div>';
                break;

            default: // give a warning
        }
    }
}
else
{
    // no action taken yet: give some instructions on debugger usage
?>

<h3>Instructions on usage of the debugger:</h3>
<ol>
<li>Run a 'list available methods' action against desired server (for SOAP servers this needs a wsdl file)</li>
<li>If list of methods appears, click on 'describe method' for desired method</li>
<li>To run method: click on 'load method synopsis' for desired method. This will load a skeleton for method call parameters in the form above. Complete all values with appropriate data and click 'Execute'</li>
<li>If you get any "call FAILED" error, use the "Show debug info" option to begin debugging</li>
</ol>
<?php
    if ( !extension_loaded( 'curl' ) )
    {
        echo "<p class=\"evidence\">You will need to enable the CURL extension to use the HTTPS and HTTP 1.1 transports</p>\n";
    }
?>

<h3>Examples:</h3>
<p>
Server Address: phpxmlrpc.sourceforge.net, Path: <a target="frmcontroller" href="./controller/?wsaction=&host=phpxmlrpc.sourceforge.net&path=/server.php&wstype=0">/server.php</a> (for xmlrpc)<br/>
Server Address: www.webservicex.net, Path: <a target="frmcontroller" href="./controller/?wsaction=list&host=www.webservicex.net&path=geoipservice.asmx?WSDL&wstype=3&wsdl=1">/geoipservice.asmx?WSDL</a> (for soap with wsdl)<br/>
Server Address: api.twitter.com, Path: /1, Method: <a target="frmcontroller" href="./controller/?wsaction=execute&host=api.twitter.com&path=/1&wsmethod=statuses/public_timeline.json&wstype=4">statuses/public_timeline.json</a> (for rest, json output)<br/>
Server Address: where.yahooapis.com, Path: /, Method: <a target="frmcontroller" href="./controller/?wsaction=execute&host=where.yahooapis.com&path=/&wsmethod=geocode&wstype=4&methodpayload={&quot;q&quot;:&quot;Klostergata 30, Skien&quot;,&quot;appid&quot;:&quot;[yourappidhere]&quot;}">geocode</a>, Parameters: q=&quot;Klostergata 30, Skien&quot;, appid=[yourappidhere] (for rest, xml output)
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
    <li>The "Response type" option is used to force proper parsing of response from servers that send incorrect Content-type headers. application/json and text/xml supported so far</li>
</ul></li>
<li>OAUTH authentication is not supported yet</li>
</ul>

<?php
}

if ( $action != 'inspect' || $debug )
{
?>
</body>
</html>
<?php
}

eZExecution::cleanExit();
?>
