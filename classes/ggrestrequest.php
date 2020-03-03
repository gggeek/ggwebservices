<?php
/**
 * Class used to wrap 'REST' requests.
 *
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 */

class ggRESTRequest extends ggWebservicesRequest
{
    /**
    * No request body for GET requests, as all params are put in the url
    */
    function payload()
    {
        if ( $this->Verb == 'GET' || $this->Verb == 'HEAD' || $this->Verb == 'TRACE' )
        {
            return '';
        }
        else
        {
            $params = $this->Parameters;
            if ( $this->NameVar != null )
            {
                $params[$this->NameVar] = $this->Name;
            }
            return $this->_payload( $params );
        }
    }

    /**
    * Final part of url that is built REST style: /methodName?p1=val1&p2=val2
    * unless request is POST (or other non-GET), then they are sent as part
    * of body.
    * Note: Flickr uses calls like this: ?method=methodName&p1=val1&p2=val2
    *       Google varies a lot
    */
    function requestURI( $uri )
    {
        $parsed = parse_url( $uri );

        $return = '';
        if ( isset( $parsed['user'] ) )
        {
            $return .= $parsed['user'] . '@' . $parsed['pass'];
        }
        $params = ( $this->Verb == 'GET' || $this->Verb == 'HEAD' || $this->Verb == 'TRACE' ) ? $this->Parameters : array();
        if ( $this->NameVar == null )
        {
            $return .= rtrim( $parsed['path'], '/' ) . '/' . ltrim( $this->Name, '/' );
        }
        else
        {
            $return .= $parsed['path'];
            if ( $this->Verb == 'GET' || $this->Verb == 'HEAD' )
            {
                $params[$this->NameVar] = $this->Name;
            }
        }

        if ( isset( $parsed['query'] ) )
        {
            $return  .= '?' . $parsed['query'];
            $next = '&';
        }
        else
        {
            $next = '?';
        }
        if ( count( $params ) )
        {
            $return .= $next . $this->_payload( $params );
        }
        if ( isset( $parsed['fragment'] ) )
        {
            $return .= '#' . $parsed['fragment'];
        }

        return $return;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function _payload( $params )
    {
        switch( $this->mimeTypeToEncodeType( $this->ContentType ) )
        {
            case 'application/x-www-form-urlencoded':
                $results = array();
                foreach( $params as $key => $val )
                {
                    if ( is_array( $val ) )
                    {
                        foreach ( $val as $vkey => $vval )
                        {
                            $results[] = urlencode( $key ) . '[' . urlencode( $vkey ) . ']=' . urlencode( $vval );
                        }
                    }
                    else
                    {
                        $results[] = urlencode( $key ) . '=' . urlencode( $val );
                    }
                }
                return implode( '&', $results );
            case 'json':
            case 'application/json':
                return json_encode( $params );
            case 'php':
            case 'application/x-httpd-php':
                return var_export( $params );
            case 'phps':
            case 'application/vnd.php.serialized':
                return serialize( $params );
        }
    }

    /**
    * Unlike other requests, $rawRequest here is not used, as GET params are used,
    * and for POST we rely on $_POST as it is already there for us.
    * This is a small break of the encapsulation principle of the API, but is
    * faster than having to push this into a specific server class.
    * While at it, we examine request headers also to determine response type for later.
    * Q: what if req. body is not multipart/form-data? A: we just ignore it...
    */
    function decodeStream( $rawRequest )
    {
        // q: what if the same param is present in both get & post? post wins, for now...
        $this->Parameters = array_merge( $_GET, $_POST );

        if ( $this->NameVar == '' )
        {
            /// recover method name from the last fragment in the URL
            if( isset( $_SERVER["PATH_INFO"] ) )
            {
                $this->Name = $_SERVER["PATH_INFO"];
            }
            else
            {
                /// @todo test if this is the good var to use for both cgi mode and when rewrite rules are in effect
                $this->Name = strrchr( $_SERVER["PHP_SELF"], '/' );
            }
            $this->Name = ltrim( $this->Name, '/' );
        }
        else
        {
            $this->Name = @$this->Parameters[$this->NameVar];
        }

        $this->ResponseType = $this->getHttpAccept();

        if ( isset( $this->Parameters[$this->JsonpVar] ) && preg_match( $this->JsonpRegexp, $this->Parameters[$this->JsonpVar] ) )
        {
            $this->JsonpCallback = $this->Parameters[$this->JsonpVar];
        }
        else
        {
            $this->JsonpCallback = false;
        }

        return true;
    }

    /**
     * Method used server-side to retrieve the accepted value from HTTP.
     * Same logic as used by ezjscore for the 'call' view, but tries to respect
     * weights in http Accept header
     *
     * @todo (!important) recover $aliasList form the response class
     */
    function getHttpAccept()
    {
        if ( isset( $_GET[$this->FormatVar] ) )
        {
            return $_GET[$this->FormatVar];
        }
        else
        {
            if ( isset( $_POST['http_accept'] ) )
                $acceptList = explode( ',', $_POST['http_accept'] );
            else if ( isset( $_POST['HTTP_ACCEPT'] ) )
                $acceptList = explode( ',', $_POST['HTTP_ACCEPT'] );
            else if ( isset( $_GET['http_accept'] ) )
                $acceptList = explode( ',', $_GET['http_accept'] );
            else if ( isset( $_GET['HTTP_ACCEPT'] ) )
                $acceptList = explode( ',', $_GET['HTTP_ACCEPT'] );
            else if ( isset( $_SERVER['HTTP_ACCEPT'] ) )
                $acceptList = explode( ',', $_SERVER['HTTP_ACCEPT'] );
            else
                $acceptList = false;

            if ( !$acceptList ) // works for false && for empty arrays too
            {
                return '';
            }

            $weightedList = array();
            foreach( $acceptList as $accept )
            {
                if ( preg_match( '/; *q *= *([0-9.]+) *$/', $accept, $matches ) )
                {
                    $accept = explode( ';', $accept );
                    $accept = trim( $accept[0] );
                    $weightedList[$accept] = (float)$matches[1];
                }
                else
                {
                    $weightedList[$accept] = 1.0;
                }
            }
            arsort( $weightedList );
            $weightedList = array_keys( $weightedList );

            // try first we the types that responses can serialize to
            /// @todo add txt, html, php, phps to the list - be loyal to other class methods
            $aliasList = array( 'json' => 'application/json', 'javascript' => 'application/json', /*'xml' => 'text/xml', 'html' => 'text/xhtml', 'text' => 'text'*/ );
            foreach( $weightedList as $accept )
            {
                if ( $accept == '*/*' )
                {
                    return reset( $aliasList );
                }
                foreach( $aliasList as $alias => $returnType )
                {
                    if ( strpos( $accept, $alias ) !== false )
                    {
                        return $returnType;
                    }
                }
            }

            // request said what it wants, but we cannot give it back as it is not supported by response
            return $weightedList[0];
        }
    }

    function contentType()
    {
        if ( $this->Verb == 'GET' || $this->Verb == 'HEAD' || $this->Verb == 'TRACE' )
        {
            return '';
        }
        return $this->ContentType;
    }

    /*
     * Content types we know how to serialize.
     * NB: list is incomplete, as there are also regexp-based rules at play
     */
    static function knownContentTypes()
    {
        return array_unique( self::$KnownContentTypes );
    }

    function requestHeaders()
    {
        /// shall we declare support for insecure stuff such as php and serialized php?
        /// NB: this must be accompanied by code that can decode the format in ggRESTResponse
        return $this->Accept != '' ? array_merge( $this->ExtraHeaders, array( 'Accept' => $this->Accept ) ) : $this->ExtraHeaders;
    }

    /// New method in this subclass
    function responseType()
    {
        return $this->ResponseType;
    }

    /// used to force type of expected response instead of finding the type from http headers (when used by client class)
    function setResponseType( $value )
    {
        $this->ResponseType = $value;
    }

    function jsonpCallback()
    {
        return $this->JsonpCallback;
    }

    /**
     * Allow easily swapping REST requests from GET to POST and viceversa
     * @param string $method
     */
    function setMethod( $method )
    {
        $this->Verb = $method;
    }

    /**
     * Allow easily changing the format of serialized requests: where to put called method name (GET/POST var, if null as last element in url path)
     * @param string $var
     */
    function setNameVar( $var )
    {
        $this->NameVar = $var;
    }

    /**
     * Only sets content-type header if we know how to serialize it later
     * @param string $typeAlias
     * @return bool
     * @todo should we allow more freedom to caller, and only map very obviously wrong things like "php", json" etc
     */
    function setContentType( $typeAlias )
    {
        // expand short-hand notation for "php", json" etc
        if ( isset( self::$KnownContentTypes[$typeAlias] ) )
        {
            $this->ContentType = self::$KnownContentTypes[$typeAlias];
            return true;
        }
        // accept as well other mimetypes, as long as we later know how to encode them
        $decodableType = $this->mimeTypeToEncodeType( $typeAlias );
        if ( isset( self::$KnownContentTypes[$decodableType] ) )
        {
            $this->ContentType = $typeAlias;
            return true;
        }
        return false;
    }

    /**
     * @param string $value
     */
    function setAccept( $value )
    {
        $this->Accept = $value;
    }

    /**
     * Used to add custom HTTP headers besides common ones (content-type, accept, auth, proxies, content-encoding, accept-encoding)
     * @param string $name
     * @param string $value
     */
    function setExtraHeader( $name, $value )
    {
        $this->ExtraHeaders[$name] = $value;
    }

    /**
     * @param string $type
     * @return string
     * @todo make more generic, incl. usage of self::$KnownContentTypes
     */
    protected function mimeTypeToEncodeType( $type )
    {
        if ( preg_match( '#^application/(vnd|prs|x)\.(.+)\+(xml|json|phps|php)$#', $type, $matches ) )
        {
            return $matches[3];
        }
        /*$reversed = array_flip( self::$KnownContentTypes );
        if ( isset( $reversed[$type] ) )
        {
            $reversed[$type];
        }*/
        return $type;
    }

    protected $Verb = 'GET';
    protected $ResponseType = '';

    /// name of GET variable used to specify method name.
    /// If empty method name is serialized as last element in url path
    protected $NameVar = null;
    /// name of GET variable used to specify desired output format.
    /// ContentType comes from ezjszcore. flickr uses 'format'
    protected $FormatVar = 'ContentType';
    /// name of GET variable used for jsonp output
    /// 'callback' is used by Yahoo, possibly google too. Flickr uses 'jsoncallback'
    protected $JsonpVar = 'callback';
    /// Regexp used to avoid XSS attacks on jsonp: only callbacks matching this
    /// expression are accepted.
    /// \w = letters, digits, underscore. See also an alternative here: http://www.json-p.org/
    protected $JsonpRegexp = '/^\w+$/';
    /// where we store the callback received in the request
    protected $JsonpCallback = false;

    protected $ContentType = 'application/x-www-form-urlencoded';
    // All of these (values) we should be able to serialize in _payload()
    protected static $KnownContentTypes = array(
        'application/x-www-form-urlencoded' => 'application/x-www-form-urlencoded',

        'json' => 'application/json',
        'application/json' => 'application/json',

        'php' => 'application/x-httpd-php',
        'application/x-httpd-php' => 'application/x-httpd-php',
        'application/x-php' => 'application/x-httpd-php',

        'phps' => 'application/vnd.php.serialized',
        'application/vnd.php.serialized' => 'application/vnd.php.serialized'
    );

    protected $Accept = 'application/json, text/xml; q=0.5';

    protected $ExtraHeaders = array();
}
