<?php
/**
 * Generic WebServices client.
 * API taken from eZSOAPClient and expanded
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2008
 *
 * @see eZSOAPClient
 *
 * @todo add parsing of response HTTP headers!!!
 * @todo add DIGEST, NTML auth if curl is enabled (for proxy auth too)
 * @todo add a version nr in user-agent string
 * @todo let user decide ssl options, set server and client certs too
 * @todo let client use keepalives for multiple subsequent calls (in curl mode)
 * @todo let client accept compressed responses (declare it in request headers)
 * @todo hide fsockopen errors using @ ?
 * @todo allow ssl connections without curl (via stream properties)
 * @todo cookie support, so that client can be uses for multiple calls with sessions
 * @todo implement following redirects (with a max predefined)
 * @todo move determination of request content type into request itself
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


abstract class ggWebservicesClient
{
    /**
      Creates a new client.
      @todo add a simplfied syntax for constructor, using parse_url and a single string
    */
    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->Login = "";
        $this->Password = "";
        $this->Server = $server;
        if ( $path == '' || $path[0] != '/' )
        {
            $path = '/' . $path;
        }
        $this->Path = $path;
        $this->Port = $port;
        /// @todo this assumes 0 is a valid port...
        if ( is_numeric( $port ) )
            $this->Port = $port;
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
    }

    /**
      Sends a request and returns the response object. 0 on error

      @todo let curl not specify http 1.0 all the time, since he knows better!
    */
    function send( $request )
    {

        if( $this->Proxy != '' )
        {
            $connectserver = $this->Proxy;
            $connectport = $this->ProxyPort;
        }
        else
        {
            $connectserver = $this->Server;
            $connectport = $this->Port;
        }

        if ( !$this->ForceCURL || !in_array( "curl", get_loaded_extensions() ) )
        {
            /// @todo add ssl support with raw sockets
            if ( $this->Timeout != 0 )
            {
                $fp = fsockopen( $connectserver,
                                 $connectport,
                                 $this->errorNumber,
                                 $this->errorString,
                                 $this->Timeout );
            }
            else
            {
                $fp = fsockopen( $connectserver,
                                 $connectport,
                                 $this->errorNumber,
                                 $this->errorString );
            }

            if ( $fp == 0 )
            {
                $this->ErrorString = '<b>Error:</b> ggWebservicesClient::send() : Unable to open connection to ' . $this->Server . '.';
                return 0;
            }
            if ( $this->Timeout != 0 )
            {
                stream_set_timeout( $fp, $this->Timeout );
            }

            $HTTPRequest = $this->payload( $request->payload() );

            if ( !fputs( $fp, $HTTPRequest, strlen( $HTTPRequest ) ) )
            {
                fclose( $fp );
                $this->ErrorString = "<b>Error:</b> could not send the request. Could not write to the socket.";
                return 0;
            }

            $rawResponse = "";
            // fetch the response
            while ( $data = fread( $fp, 32768 ) )
            {
                $rawResponse .= $data;
            }
            // close the socket
            fclose( $fp );
        }
        else
        {
            $URL = $this->Protocol . "://" . $connecterver . ":" . $this->Port . $this->Path;
            $ch = curl_init ( $URL );

            if ( $ch != 0 )
            {
                if ( $this->Timeout != 0 )
                {
                    curl_setopt( $ch, CURLOPT_TIMEOUT, $this->TimeOut );
                }

                $HTTPCall = $this->payload( $request->payload() );

                curl_setopt( $ch, CURLOPT_URL, $URL );

                if( $this->Proxy != '' )
                {
                    curl_setopt($curl, CURLOPT_PROXY, $this->Proxy . ':' . $this->ProxyPort );
                }

                /// @todo only set this in ssl mode, plus set user decide
                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
                curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 1 );

                curl_setopt( $ch, CURLOPT_HEADER, 1 );
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $HTTPCall );  // Don't use CURLOPT_CUSTOMREQUEST without making sure your server supports the custom request method first.
                //unset( $rawResponse );

                $rawResponse = curl_exec( $ch );
                curl_close( $ch );

                if ( !$rawResponse )
                {
                    $this->ErrorString = "<b>Error:</b> could not send the request. Could not write to the socket.";
                    return 0;
                }
            }
            else
            {
                    $this->ErrorString = "<b>Error:</b> could not send the request. Could not initialize CURL.";
                    return 0;
            }
        }

        $response = new $this->ResponseClass ();
        $response->decodeStream( $request, $rawResponse );
        return $response;
    }

    /**
    * Build and return full HTTP payload out of a request payload (and other server status vars)
    */
    protected function payload( $payload )
    {
        $authentification = "";
        if ( ( $this->login() != "" ) )
        {
            $authentification = "Authorization: Basic " . base64_encode( $this->login() . ":" . $this->password() ) . "\r\n" ;
        }

        $proxy_credentials = '';
        if( $this->Proxy != '' )
        {
            $uri = $this->Protocol . '://' . $this->Server . ':' . $this->Port . $this->Path;
            if( $this->ProxyLogin != '' )
            {
                if ( $this->ProxyAuthType != 1 )
                {
                    //error_log('Only Basic auth to proxy is supported yet');
                }
                $proxy_credentials = 'Proxy-Authorization: Basic ' . base64_encode( $this->ProxyLogin . ':' . $this->ProxyPassword ) . "\r\n";
            }
        }
        else
        {
            // if no proxy in use, URLS in request are not absolute but relative
            $uri = $this->Path;
        }

        $HTTPRequest = $this->Verb . " " . $uri . " HTTP/1.0\r\n" .
            "User-Agent: " . $this->UserAgent ."\r\n" .
            "Host: " . $this->Server . ":" . $this->Port . "\r\n" .
            $authentification .
            $proxy_credentials;

        // added extra request headers for eg SOAP clients
        foreach( $this->RequestHeaders as $header => $value )
        {
            $HTTPRequest .= $header . ": " . $value . "\r\n";
        }

        if ( $payload !== '' )
        {

            if( function_exists( 'gzdeflate' ) && ( $this->RequestCompression == 'gzip' || $this->RequestCompression == 'deflate' ) )
            {
                if( $this->request_compression == 'gzip' )
                {
                    $a = @gzencode($payload);
                    if( $a )
                    {
                        $payload = $a;
                        $HTTPRequest .= "Content-Encoding: gzip\r\n";
                    }
                }
                else
                {
                    $a = @gzcompress( $payload );
                    if( $a )
                    {
                        $payload = $a;
                        $HTTPRequest .= "Content-Encoding: deflate\r\n";
                    }
                }
            }

            $HTTPRequest .=
                "Content-Type: " . $this->ContentType . "\r\n" .
                "Content-Length: " . strlen( $payload ) . "\r\n\r\n" . $payload;
        }
        else
        {
            $HTTPRequest .= "\r\n";
        }

        return $HTTPRequest;
    }

    /**
     Set timeout value

     @param int $timeout value in seconds. Set to 0 for unlimited.
    */
    function setTimeout( $timeout )
    {
        $this->Timeout = $timeout;
    }

    /**
     Sets the HTTP login
    */
    function setLogin( $login  )
    {
        $this->Login = $login;
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
    */
    function setPassword( $password  )
    {
        $this->Password = $password;
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
    */
    function setProxy( $proxyhost, $proxyport, $proxyusername = '', $proxypassword = '', $proxyauthtype = 1 )
    {
        $this->Proxy = $proxyhost;
        $this->ProxyPort = ( (int)$proxyport != 0 ? (int)$proxyport : 8080 );
        $this->ProxyUser = $proxyusername;
        $this->ProxyPassword = $proxypassword;
        $this->ProxyAuthType = $proxyauthtype;
    }

    function setMethod( $verb )
    {
        $this->Verb = strtoupper( $verb );
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

    protected $ContentType = 'text/xml'; // set up a default that is most likely
    protected $UserAgent =  'gg eZ webservices client';
    protected $Protocol = 'http';
    protected $ResponseClass = 'ggWebservicesResponse';
    protected $RequestHeaders = array();
    protected $ForceCURL = false;
    protected $Verb = 'POST';
    protected $RequestCompression = '';
    protected $Proxy = '';
    protected $ProxyPort = 0;
    protected $ProxyLogin = '';
    protected $ProxyPassword = '';
    protected $ProxyAuthType = 1;
    // below here: yet to be used...
    protected $AuthType = 1;
    protected $Cert = '';
    protected $CertPass = '';
    protected $CACert = '';
    protected $CACertDir = '';
    protected $Key = '';
    protected $KeyPass = '';
    protected $VerifyPeer = true;
    protected $VerifyHost = 1;
}

?>
