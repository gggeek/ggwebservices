<?php
/**
 * Generic WebServices client.
 * API taken from eZSOAPClient and expanded
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 *
 * @see eZSOAPClient
 *
 * @todo add DIGEST, NTML auth if curl is enabled (for proxy auth too)
 * @todo add a version nr in user-agent string
 * @todo let user decide ssl options, set server and client certs too
 * @todo let client use keepalives for multiple subsequent calls (in curl mode)
 * @todo let client accept compressed responses (declare it in request headers)
 * @todo allow ssl connections without curl (via stream properties)
 * @todo finish cookie support, so that client can be used for multiple calls with sessions
 * @todo implement following redirects (with a max predefined)
 *
 * changes from eZSOAP client:
 * - added support for proxy
 * - added capability to send compressed request payloads
 * - allowed curl to be used for http too (not yet exposed to class user)
 * - fix Host http header if port != 80 (needed for proxies)
 * - close socket before returning if error on write
 * - added timeout on socket read/write, not only on socket opening
 * - check that path starts with a slash char (also fixes empty path pbl)
 * - made client capable to do GET requests
 * - allow client to do https on ports != 443 (via modified constructor)
 */


class ggWebservicesClient
{
    const ERROR_MISSING_CURL = -101;
    const ERROR_CANNOT_CONNECT = -102;
    const ERROR_CANNOT_WRITE = -103;
    const ERROR_NO_DATA = -104;
    const ERROR_BAD_RESPONSE = -105;

    /**
     * Creates a new client.
     * @param int|string $port 'ssl' can be used for https connections, or port number
     * @param string $protocol use 'https' (or bool true, for backward compatibility) to specify https connections
     * @todo add a simplfied syntax for constructor, using parse_url and a single string
     */
    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        // backward compat with ezsoap client
        if ( $protocol === true )
        {
            $protocol = 'https';
        }
        $this->Login = '';
        $this->Password = '';
        $this->Server = $server;
        if ( $path == '' || $path[0] != '/' )
        {
            $path = '/' . $path;
        }
        $this->Path = $path;
        $this->Port = $port;
        // this assumes 0 is a valid port. Weird, but useful for unix domain sockets..
        if ( is_numeric( $port ) )
            $this->Port = (int)$port;
        elseif( strtolower( $port ) == 'ssl' || $protocol == 'https' )
            $this->Port = 443;
        else
            $this->Port = 80;
        if ( $protocol == null )
        {
            // lame, but we know no better
            if ( $this->Port == 443 )
            {
                $this->Protocol = 'https';
            }
            else
            {
                $this->Protocol = 'http';
            }
        }
        else
        {
            $this->Protocol = $protocol;
        }

        // if ZLIB is enabled, let the client by default accept compressed responses
        if( function_exists( 'gzinflate' ) || (
            function_exists( 'curl_init' ) && ( ( $info = curl_version() ) &&
            ( ( is_string( $info ) && strpos( $info, 'zlib' ) !== null ) || isset( $info['libz_version'] ) ) ) ) )
        {
            $this->AcceptedCompression = 'gzip, deflate';
        }
    }

    /**
     * Sends a request and returns the response object. 0 on error
     *
     * @todo let curl not specify http 1.0 all the time, since he knows better!
     */
    function send( $request )
    {

        if ( $this->Proxy != '' )
        {
            $connectserver = $this->Proxy;
            $connectport = $this->ProxyPort;
        }
        else
        {
            $connectserver = $this->Server;
            $connectport = $this->Port;
            if ( $this->Protocol == 'https' )
            {
                $connectserver = 'ssl://' . $connectserver;
            }
        }

        $this->errorString = '';
        $this->errorNumber = 0;
        // we default to NOT using cURL if not asked to (or if it is not there)
        if ( !$this->ForceCURL || !in_array( "curl", get_loaded_extensions() ) )
        {
            if ( $this->ForceCURL )
            {
                $this->errorNumber = self::ERROR_MISSING_CURL;
                $this->errorString = "Error: could not send the request. CURL not installed.";
                return 0;
            }

            // generate payload before opening socket, for a smaller connection time
            $HTTPRequest = $this->payload( $request );

            /// @todo add ssl support with raw sockets
            if ( $this->Timeout != 0 )
            {
                $fp = @fsockopen( $connectserver,
                                  $connectport,
                                  $this->errorNumber,
                                  $this->errorString,
                                  $this->Timeout );
            }
            else
            {
                $fp = @fsockopen( $connectserver,
                                  $connectport,
                                  $this->errorNumber,
                                  $this->errorString );
            }
            if ( $fp == 0 )
            {
                // nb: can we feed back to end user the error codes from fsockopen?
                // They come basically from errno.h on unix - http://www.finalcog.com/c-error-codes-include-errno
                // OR WSAGetLastError() on windows - http://msdn.microsoft.com/en-us/library/ms740668(VS.85).aspx
                // this makes them very unreliable to test using either numeric values or constants
                // so we just dump them into the error string, and use a small list
                // of error codes defined locally in the client class
                $this->errorString = $this->errorNumber . ' - ' . $this->errorString;
                $this->errorNumber = self::ERROR_CANNOT_CONNECT;
                return 0;
            }

            if ( $this->Timeout != 0 )
            {
                stream_set_timeout( $fp, $this->Timeout );
            }

            if ( !fwrite( $fp, $HTTPRequest, strlen( $HTTPRequest ) ) )
            {
                fclose( $fp );
                $this->errorNumber = self::ERROR_CANNOT_WRITE;
                $this->errorString = "Error: could not send the request. Could not write to the socket.";
                return 0;
            }

            $rawResponse = "";
            // fetch the response
            do
			{
                /// could we rely on getting false as a sure sign of error and return an ERROR_CANNOT_READ here ?
                $rawResponse .= fread( $fp, 32768 );
            } while( $fp && !feof( $fp ) );
            // close the socket
            fclose( $fp );
        }
        else
        {
            $URL = $this->Protocol . "://" . $this->Server . ":" . $this->Port . $this->Path . $request->querystring();
            $ch = curl_init ( $URL );

            if ( $ch != 0 )
            {
                curl_setopt( $ch, CURLOPT_HEADER, 1 );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

                if ( $this->Timeout != 0 )
                {
                    curl_setopt( $ch, CURLOPT_TIMEOUT, $this->TimeOut );
                }

                //curl_setopt( $ch, CURLOPT_URL, $URL );

                if( $this->login() != "" )
                {
                    curl_setopt( $ch, CURLOPT_USERPWD, $this->login() . ':' . $this->password() );
                    curl_setopt( $ch, CURLOPT_HTTPAUTH, $this->AuthType );
                }

                if( $this->Proxy != '' )
                {
                    curl_setopt( $ch, CURLOPT_PROXY, $this->Proxy . ':' . $this->ProxyPort );
                    if( $this->ProxyLogin != '' )
                    {
                        curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $this->ProxyLogin . ':' . $this->ProxyPassword );
                        /// @todo check curl version: need 7.1.7 min for CURLOPT_PROXYAUTH
                        curl_setopt( $ch, CURLOPT_PROXYAUTH, $this->ProxyAuthType );
                    }

                }

                if ( $this->UserAgent != '' )
                {
                    curl_setopt( $ch, CURLOPT_USERAGENT, $this->UserAgent );
                }

                /// @todo only set this in ssl mode, plus set user decide
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 1 );

                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $this->payload( $request ) );  // Don't use CURLOPT_CUSTOMREQUEST without making sure your server supports the custom request method first.

                $rawResponse = curl_exec( $ch );

                if ( $rawResponse === false )
                {
                    // grepping through curl sources, we find out the curl error range
                    // to be 1 to 82 (as of curl 7.19.4)
                    $this->errorNumber = curl_errno( $ch ) * -1;
                    $this->errorString = curl_error( $ch );
                    curl_close( $ch );
                    return 0;
                }
                else
                {
                    curl_close( $ch );
                }

            }
            else
            {
                $this->errorNumber = CURLE_FAILED_INIT * -1;
                $this->errorString = "Error: could not send the request. Could not initialize CURL.";
                return 0;
            }
        }

        $respArray = $this->parseHTTPResponse( $rawResponse );
        // in case an HTTP error is encountered, error number and string will have been set
        if ( $respArray === false )
        {
            return 0;
        }
        $this->Cookies = $respArray['cookies'];

        $ResponseClass =  $this->ResponseClass;
        if ( $ResponseClass == 'ggWebservicesResponse' )
        {
            // the client subclass in use did not bother to specify a class for responses:
            // assume the reponse class is named after the request
            if ( preg_match( '/^(.+)Request$/', get_class( $request ), $matches ) && class_exists( $matches[1] . 'Response' ) )
            {
                $ResponseClass = $matches[1] . 'Response';
            }
        }
        $response = new $ResponseClass( $request->name() );

        $response->decodeStream( $request, $rawResponse, $respArray['headers'] );
        return $response;
    }

    /**
     * Build and return full HTTP payload out of a request payload (and other server status vars)
     * @param ggWebservicesRequest $request
     * @return string
     */
    protected function payload( $request )
    {
        $query_string = $request->querystring();

        if( $this->Proxy != '' )
        {
            // if proxy in use, URLS in request are absolute
            $uri = $this->Protocol . '://' . $this->Server . ':' . $this->Port . $this->Path . $query_string;
            if( $this->ProxyLogin != '' )
            {
                if ( $this->ProxyAuthType != 1 )
                {
                    //error_log('Only Basic auth to proxy is supported yet');
                }
                $RequestHeaders = array( 'Proxy-Authorization' => 'Basic ' . base64_encode( $this->ProxyLogin . ':' . $this->ProxyPassword ) );
            }
        }
        else
        {
            // if no proxy in use, URLS in request are not absolute but relative
            $uri = $this->Path . $query_string;
            $RequestHeaders = array();
        }

        // backward compatibility: if request does not specify a verb, let the client
        // pick one on its own
        $verb = $request->method();
        if ( $verb == '' )
        {
            $verb = $this->Verb;
        }
        $HTTPRequest = $verb . " " . $uri . " HTTP/1.0\r\n" .
            /// @todo do not add PORT info if on port 80
            "Host: " . $this->Server . ":" . $this->Port . "\r\n";

        // added extra request headers for eg SOAP clients
        /// @bug what if both client and request want to add eg COOKIES?
        $RequestHeaders = array_merge( $this->RequestHeaders, $request->RequestHeaders(), $RequestHeaders );

        $authentification = "";
        if ( $this->login() != "" )
        {
            if ( $this->AuthType != 1 )
            {
                //error_log('Only Basic auth is supported');
            }
            $RequestHeaders['Authorization'] = "Basic " . base64_encode( $this->login() . ":" . $this->password() ) . "\r\n" ;
        }

        if ( $this->UserAgent != '' )
        {
            $RequestHeaders['User-Agent'] = $this->UserAgent;
        }

        if ( $this->AcceptedCompression != '' );
        {
            $RequestHeaders['Accept-Encoding'] = $this->AcceptedCompression;
        }

        $payload = $request->payload();
        if ( $payload !== '' )
        {

            if( ( $this->RequestCompression == 'gzip' || $this->RequestCompression == 'deflate' ) && function_exists( 'gzdeflate' ) )
            {
                if( $this->request_compression == 'gzip' )
                {
                    $a = @gzencode( $payload );
                    if( $a )
                    {
                        $payload = $a;
                        $RequestHeaders['Content-Encoding'] = 'gzip';
                    }
                }
                else
                {
                    $a = @gzcompress( $payload );
                    if( $a )
                    {
                        $payload = $a;
                        $RequestHeaders['Content-Encoding'] = 'deflate';
                    }
                }
            }

            $ContentType =$request->contentType();
            if ( $ContentType == '' )
            {
                $ContentType = $this->ContentType;
            }
            $RequestHeaders['Content-Type'] = $ContentType;
            $RequestHeaders['Content-Length'] = strlen( $payload );
        }

        foreach ( $RequestHeaders as $key => $val )
        {
            $RequestHeaders[$key] = "$key: $val";
        }
        /// @bug in case there is no $RequestHeaders, we send one crlf too much?
        return $HTTPRequest . implode( "\r\n", $RequestHeaders ) . "\r\n\r\n" . $payload;
    }

    /**
    * HTTP parsing code taken from the phpxmlrpc lib
    * @todo look at PEAR, ZEND, other libs, if they do it better...
    */
    protected function parseHTTPResponse( &$data, $headers_processed=false )
    {
        if ( $data == '' )
        {
            return array( self::ERROR_NO_DATA, 'No data received from server.' );
        }

        // Support "web-proxy-tunelling" connections for https through proxies
        if ( preg_match( '/^HTTP\/1\.[0-1] 200 Connection established/', $data ) )
        {
            // Look for CR/LF or simple LF as line separator,
            // (even though it is not valid http)
            $pos = strpos( $data,"\r\n\r\n" );
            if( $pos !== false )
            {
                $bd = $pos + 4;
            }
            else
            {
                $pos = strpos( $data, "\n\n" );
                if( $pos !== false )
                {
                    $bd = $pos + 2;
                }
                else
                {
                    // No separation between response headers and body: fault?
                    $this->errorNumber = self::ERROR_BAD_RESPONSE;
                    $this->errorString = "HTTPS via proxy error, tunnel connection possibly failed.";
                    return false;
                }
            }
            // this filters out all http headers from proxy.
            // maybe we could take them into account, too?
            $data = substr( $data, $bd );
        }

        // Strip HTTP 1.1 100 Continue header if present
        while( preg_match( '/^HTTP\/1\.1 1[0-9]{2} /', $data ) )
        {
            $pos = strpos( $data, 'HTTP', 12 );
            // server sent a Continue header without any (valid) content following...
            // give the client a chance to know it
            if( $pos === false )
            {
                break;
            }
            $data = substr( $data, $pos );
        }
        if( !preg_match( '/^HTTP\/[0-9.]+ 200 /', $data ) )
        {
            $errstr = substr( $data, 0, strpos( $data, "\n" ) - 1 );
            $this->errorNumber = self::ERROR_BAD_RESPONSE;
            $this->errorString = 'HTTP error (' . $errstr . ')';
            return false;
        }

        $headers = array();
        $cookies = array();

        // be tolerant to usage of \n instead of \r\n to separate headers and data
        // (even though it is not valid http)
        $pos = strpos( $data, "\r\n\r\n" );
        if( $pos !== false )
        {
            $bd = $pos + 4;
        }
        else
        {
            $pos = strpos( $data, "\n\n" );
            if( $pos !== false )
            {
                $bd = $pos + 2;
            }
            else
            {
                // No separation between response headers and body: fault?
                // we could take some action here instead of going on...
                $bd = 0;
            }
        }
        // be tolerant to line endings, and extra empty lines
        $ar = preg_split( "/\r?\n/", trim( substr( $data, 0, $pos ) ) );
        foreach( $ar as $line )
        {
            // take care of multi-line headers and cookies
            $arr = explode( ':', $line, 2 );
            if( count( $arr ) > 1 )
            {
                $header_name = strtolower( trim( $arr[0] ) );
                /// @todo some other headers (the ones that allow a CSV list of values)
                /// do allow many values to be passed using multiple header lines.
                /// We should add content to $headers[$header_name]
                /// instead of replacing it for those...
                if ( $header_name == 'set-cookie' || $header_name == 'set-cookie2' )
                {
                    if ( $header_name == 'set-cookie2' )
                    {
                        // version 2 cookies:
                        // there could be many cookies on one line, comma separated
                        $lcookies = explode( ',', $arr[1] );
                    }
                    else
                    {
                        $lcookies = array( $arr[1] );
                    }
                    foreach ( $lcookies as $cookie )
                    {
                        // glue together all received cookies, using a comma to separate them
                        // (same as php does with getallheaders())
                        if ( isset( $headers[$header_name] ) )
                            $headers[$header_name] .= ', ' . trim( $cookie );
                        else
                            $headers[$header_name] = trim( $cookie );
                        // parse cookie attributes, in case user wants to correctly honour them
                        // feature creep: only allow rfc-compliant cookie attributes?
                        // @todo support for server sending multiple time cookie with same name, but using different PATHs
                        $cookie = explode( ';', $cookie );
                        foreach ( $cookie as $pos => $val )
                        {
                            $val = explode( '=', $val, 2 );
                            $tag = trim( $val[0] );
                            $val = trim( @$val[1] );
                            /// @todo with version 1 cookies, we should strip leading and trailing " chars
                            if ( $pos == 0 )
                            {
                                $cookiename = $tag;
                                $cookies[$tag] = array();
                                $cookies[$cookiename]['value'] = urldecode($val);
                            }
                            else
                            {
                                if ($tag != 'value')
                                {
                                    $cookies[$cookiename][$tag] = $val;
                                }
                            }
                        }
                    }
                }
                else
                {
                    $headers[$header_name] = trim( $arr[1] );
                }
            }
            elseif( isset( $header_name ) )
            {
                ///	@todo version1 cookies might span multiple lines, thus breaking the parsing above
                $headers[$header_name] .= ' ' . trim( $line );
            }
        }

        $data = substr( $data, $bd );

        // if CURL was used for the call, http headers have been processed,
        // and dechunking + reinflating have been carried out
        if( !$headers_processed )
        {
            // Decode chunked encoding sent by http 1.1 servers
            if( isset( $headers['transfer-encoding'] ) && $headers['transfer-encoding'] == 'chunked' )
            {
                if( !$data = self::decode_chunked( $data ) )
                {
                    $this->errorNumber = self::ERROR_BAD_RESPONSE;
                    $this->errorString = "Errors occurred when trying to rebuild the chunked data received from server.";
                    return false;
                }
            }

            // Decode gzip-compressed stuff
            // code shamelessly inspired from nusoap library by Dietrich Ayala
            if( isset( $headers['content-encoding'] ) )
            {
                $headers['content-encoding'] = str_replace( 'x-', '', $headers['content-encoding'] );
                if( $headers['content-encoding'] == 'deflate' || $headers['content-encoding'] == 'gzip' )
                {
                    // if decoding works, use it. else assume data wasn't gzencoded
                    if( function_exists( 'gzinflate' ) )
                    {
                        if( $headers['content-encoding'] == 'deflate' && $degzdata = @gzuncompress( $data ) )
                        {
                            $data = $degzdata;
                        }
                        elseif($headers['content-encoding'] == 'gzip' && $degzdata = @gzinflate( substr( $data, 10 ) ) )
                        {
                            $data = $degzdata;
                        }
                        else
                        {
                            $this->errorNumber = self::ERROR_BAD_RESPONSE;
                            $this->errorString = "Errors occurred when trying to decode the deflated data received from server.";
                            return false;
                        }
                    }
                    else
                    {
                        $this->errorNumber = self::ERROR_BAD_RESPONSE;
                        $this->errorString = "The server sent deflated data. This php install must have the Zlib extension compiled in to support this.";
                        return false;
                    }
                }
            }
        } // end of 'if needed, de-chunk, re-inflate response'

        return array( 'headers' => $headers, 'cookies' => $cookies );
    }

    /**
     * decode a string that is encoded w. "chunked" transfer encoding
     * as defined in rfc2068 par. 19.4.6
     * Code shamelessly stolen from nusoap library by Dietrich Ayala
     *
     * @param string $buffer the string to be decoded
     * @return string
     */
    protected static function decode_chunked( $buffer )
    {
        $length = 0;
        $new = '';

        // read chunk-size, chunk-extension (if any) and crlf
        // get the position of the linebreak
        $chunkend = strpos($buffer,"\r\n") + 2;
        $temp = substr($buffer,0,$chunkend);
        $chunk_size = hexdec( trim($temp) );
        $chunkstart = $chunkend;
        while($chunk_size > 0)
        {
            $chunkend = strpos($buffer, "\r\n", $chunkstart + $chunk_size);

            // just in case we got a broken connection
            if($chunkend == false)
            {
                $chunk = substr($buffer,$chunkstart);
                // append chunk-data to entity-body
                $new .= $chunk;
                $length += strlen($chunk);
                break;
            }

            // read chunk-data and crlf
            $chunk = substr($buffer,$chunkstart,$chunkend-$chunkstart);
            // append chunk-data to entity-body
            $new .= $chunk;
            // length := length + chunk-size
            $length += strlen($chunk);
            // read chunk-size and crlf
            $chunkstart = $chunkend + 2;

            $chunkend = strpos($buffer,"\r\n",$chunkstart)+2;
            if($chunkend == false)
            {
                break; //just in case we got a broken connection
            }
            $temp = substr($buffer,$chunkstart,$chunkend-$chunkstart);
            $chunk_size = hexdec( trim($temp) );
            $chunkstart = $chunkend;
        }
        return $new;
    }

    /*
       One-stop shop for setting all configuration options
       without haviong to write a haundred method calls
       @todo move all of these values to an array, for commodity
       @todo return true if option exists, false otherwhise?
   */
    function setOption( $option, $value )
    {
        switch( $option )
        {
            case 'timeout':
                $this->Timeout = (int)$value;
                break;
            case 'login':
                $this->Login = $value;
                break;
            case 'password':
                $this->Password = $value;
                break;
            case 'authType':
                $this->AuthType = (int)$value;
                if ( $value != 1 )
                {
                    $this->ForceCURL = true;
                }
                break;
            case 'requestCompression':
                $this->RequestCompression = $value;
                break;
            case 'method':
                $this->Verb = strtoupper( $alue );
                break;
            case 'acceptedCompression':
                $this->AcceptedCompression = $value;
                break;
            case 'proxyHost':
                $this->Proxy = $value;
                break;
            case 'proxyPort':
                $this->ProxyPort = ( (int)$value != 0 ? (int)$value : 8080 );
                break;
            case 'proxyUser':
                $this->ProxyUser = $value;
                break;
            case 'proxyPassword':
                $this->ProxyPassword = $value;
                break;
            case 'proxyAuthType':
                $this->ProxyAuthType = (int)$value;
                if ( $value != 1 )
                {
                    $this->ForceCURL = true;
                }
                break;
            case 'forceCURL':
                $this->ForceCURL = (bool)$value;
                break;

        }

    }

    /**
    *  Set many options in one fell swoop
    * @param array $optionArray
    */
    function setOptions( $optionArray )
    {
        foreach( $optionArray as $name => $value )
        {
            $this->setOption( $name, $value );
        }
    }

    /**
     Set timeout value

     @param int $timeout value in seconds. Set to 0 for unlimited.
     @deprecated use setOption instead
    */
    function setTimeout( $timeout )
    {
        $this->setOption( 'timeout', $timeout );
    }

    /**
     Sets the HTTP login
     @deprecated use setOption instead
    */
    function setLogin( $login  )
    {
        $this->setOption( 'login', $login );
    }

    /**
      Returns the login, used for HTTP authentification
    */
    function login()
    {
        return $this->Login;
    }

    /**
     Sets the HTTP password
     @deprecated use setOption instead
    */
    function setPassword( $password  )
    {
        $this->setOption( 'password', $password );
    }

    /**
      Returns the password, used for HTTP authentification
    */
    function password()
    {
        return $this->Password;
    }

    /**
     * Enable sending compressed requests (needs zlib extension installed)
     * Valid values: 'deflate, 'gzip', null
     * @deprecated use setOption instead
     */
    function setRequestCompression( $compmethod )
    {
        $this->RequestCompression = $compmethod;
    }

    /**
     * Set proxy info
     * @param string $proxyhost
     * @param string $proxyport Defaults to 8080 for HTTP and 443 for HTTPS
     * @param string $proxyusername Leave blank if proxy has public access
     * @param string $proxypassword Leave blank if proxy has public access
     * @param int $proxyauthtype set to constant CURLAUTH_NTLM to use NTLM auth with proxy
     * @access public
     * @deprecated use setOptions instead
     */
    function setProxy( $proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1 )
    {
        $this->setOptions( array(
            'proxyHost' => $proxyhost,
            'ProxyPort' => $proxyport,
            'ProxyUser' => $proxyusername,
            'ProxyPassword' => $proxypassword,
            'ProxyAuthType' => $proxyauthtype
        ) );
    }

    /**
    * Used to switch http method used to either POST (default) or GET when using
    * webservice protocols that allow both (eg. REST?)
    * @deprecated use the method from $request
    */
    function setMethod( $verb )
    {
        $this->Verb = strtoupper( $verb );
    }

    function errorString()
    {
        return $this->errorString;
    }

    function errorNumber()
    {
        return $this->errorNumber;
    }

    /// The name or IP of the server to communicate with
    protected $Server;
    /// The path to the server
    protected $Path;
    /// The port of the server to communicate with.
    protected $Port;
    /// How long to wait for the call.
    protected $Timeout = 0;
    /// HTTP login for HTTP authentification
    protected $Login;
    /// HTTP password for HTTP authentification
    protected $Password;
    /// @see CURLOPT_AUTH for values
    protected $AuthType = 1;
    protected $UserAgent = 'gg eZ webservices client';
    protected $Protocol = 'http';
    protected $ResponseClass = 'ggWebservicesResponse';
    protected $ForceCURL = false;
    protected $RequestCompression = '';
    protected $Proxy = '';
    protected $ProxyPort = 0;
    protected $ProxyLogin = '';
    protected $ProxyPassword = '';
    protected $ProxyAuthType = 1;
    protected $AcceptedCompression = '';

    // below here: yet to be used...
    protected $Cert = '';
    protected $CertPass = '';
    protected $CACert = '';
    protected $CACertDir = '';
    protected $Key = '';
    protected $KeyPass = '';
    protected $VerifyPeer = true;
    protected $VerifyHost = 1;
    protected $KeepAlive = true;

    protected $errorString = '';
    protected $errorNumber = 0;

    protected $Cookies = array();

    // The following 3 members exist for historical reasons only - they should be
    // removed from the client and moved only to the request classes
    protected $ContentType = 'text/xml'; // set up a default that is most likely
    protected $RequestHeaders = array();
    protected $Verb = 'POST';

}

?>