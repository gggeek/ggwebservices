<?php
/**
 * Class used to wrap 'REST' requests.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2011
 */

class ggRESTRequest extends ggWebservicesRequest
{
    /**
    * No request body for GET requests, as all aparams are put in the url
    */
    function payload()
    {
        if ( $this->Verb == 'GET' )
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
        $params = ( $this->Verb == 'GET' ) ? $this->Parameters : array();
        if ( $this->NameVar == null )
        {
            $return .= rtrim( $parsed['path'], '/' ) . '/' . $this->Name;
        }
        else
        {
            $return .= $parsed['path'];
            if ( $this->Verb == 'GET' )
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

    protected function _payload( $params )
    {
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
            /// @todo add txt, html, php, phps to the list
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
        if ( $this->Verb == 'GET' )
        {
            return '';
        }
        return 'application/x-www-form-urlencoded';
    }

    function requestHeaders()
    {
        /// shall we declare support for insecure stuff such as php and serialized php?
        /// NB: this must be accompanied by code that can decode the format in ggRESTResponse
        return array( 'Accept' => 'application/json, text/xml; q=0.5' );
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

    // allow easily swapping REST requests from GET to POST and viceversa
    function setMethod( $method )
    {
        $this->Verb = $method;
    }

    // allow easily changing the format of serialized requests: where to put called method name (GET/POST var, if null as last element in url path)
    function setNameVar( $var )
    {
        $this->NameVar = $var;
    }

    protected $Verb = 'GET';
    protected $ResponseType = '';

    /// name of GET variable used to specify method name.
    /// If empty; method name is serialized as last element in url path
    protected $NameVar = null;
    /// name of GET variable used to specify output format.
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
}

?>