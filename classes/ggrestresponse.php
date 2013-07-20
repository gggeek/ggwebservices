<?php
/**
 * Class used to wrap REST responses.
 * Needs the simplexml extension when serializing in xml, the json ext. for json
 *
 * @author G. Giunta
 * @copyright (C) 2009-2013 G. Giunta
 */

class ggRESTResponse extends ggWebservicesResponse
{

    const INVALIDRESPONSESTRING = 'Response received from server is not valid';

    /**
    * Returns the payload for the response.
    * Encoding varies depending on what the request asked for
    *
    * @todo add jsonp support for php? note that the validation regexp (in req. class) might need to differ...
    */
    function payload( )
    {
        $contentType = $this->ContentType;
        // accommodate re-serializing content after we got a forced content-type
        if ( $pos = strpos( $contentType, ';' ) !== false )
        {
            $contentType = substr( $contentType, 0, $pos );
        }
        if ( $contentType == '' )
        {
            $contentType = $this->defaultContentType;
        }

        if ( $this->IsFault )
        {
            // default representation of an error, hand picked
            $value = array( 'faultCode' => $this->FaultCode, 'faultString' => $this->FaultString );
            // send an HTTP error code, since there is no other way to make sure
            // that the client can tell apart error responses from valid array responses
            // try some meaningful mapping (it's REST, baby!)
            switch( $this->FaultCode )
            {
                case ggWebservicesServer::INVALIDMETHODERROR:
                    header( $_SERVER['SERVER_PROTOCOL'].' 404 Not Found' );
                    break;
                case ggWebservicesServer::INVALIDPARAMSERROR:
                case ggWebservicesServer::INVALIDCOMPRESSIONERROR:
                case ggWebservicesServer::INVALIDREQUESTERROR:
                    header( $_SERVER['SERVER_PROTOCOL'].' 400 Bad Request' );
                    break;
                case ggWebservicesServer::INVALIDAUTHERROR:
                    header( $_SERVER['SERVER_PROTOCOL'].' 403 Forbidden' );
                    break;
                default:
                    header( $_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error' );
            }
        }
        else
        {
            $value = $this->Value;
        }

        switch( $contentType )
        {
            case 'php':
            case 'application/x-httpd-php':
                return var_export( $value );
            case 'phps':
            case 'application/vnd.php.serialized':
                return serialize( $value );
            case 'application/json':
                $json = json_encode( $value, JSON_FORCE_OBJECT );
                if ( $this->JsonpCallback != false )
                {
                    $json = $this->JsonpCallback . '(' . $json . ')';
                }
                return $json;
            case 'text/plain':
                return is_string( $value ) ? $value : var_export( $value, true );
            case 'text/html':
                // in theory both html and body tags are not mandatory. But getting here is just a case of bad coding anyway
                return is_string( $value ) ? $value : '<html><body>' . htmlspecialchars( print_r( $value, true ) ) . '</body></html>';
            default:
                header('HTTP/1.1 406 Not Acceptable');
                // two 'non standard but existing' mimetype defs for php code and serialized
                return "REST responses cannot be serialized as '$contentType'. Currently supported: application/json, application/x-httpd-php, application/vnd.php.serialized";
        }

    }

    /**
    * Decodes the REST response stream.
    * Request is not used, kept for compat with sibling classes
    * Name is not set to response from request - a bit weird...
    * @param ggRESTRequest $request
    */
    function decodeStream( $request, $stream, $headers=false, $cookies=array(), $statuscode="200" )
    {
        $this->decodeStreamCommon( $request, $stream, $headers, $cookies, $statuscode );

        $contentType = $this->ContentType;
        $charset = $this->Charset;

        // allow request to force an expected response type
        // this is useful if servers do respond with eg. text/plain content-header for json or xml
        if ( ( $forcedType = $request->responseType() ) != '' )
        {
            list ( $contentType, $charset2 ) = $this->parseContentTypeHeader( $forcedType );
            $this->ContentType = $contentType . '; received="' . $this->ContentType . '"';
            if ( $charset2 != '' )
            {
                $charset = $charset2;
                $this->Charset = $charset . '; received="' . $this->Charset . '"';
            }
        }

        // Allow empty payloads regardless of declared content type, for 204 and 205 responses
        if ( $statuscode == '204' || $statuscode == '205' )
        {
            if ( $stream == '' && ( !isset( $headers['content-length'] ) || $headers['content-length'] == 0 ) )
            {
                $this->Value = null;
                return;
            }
            else
            {
                /// @todo this is not valid according to rfc 2616 - but we should leave that control to client really
                $this->IsFault = true;
                $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                $this->FaultString = ggRESTResponse::INVALIDRESPONSESTRING . " (received http response 204/205 with a body. Not valid http)";
            }
        }

        /// @todo take into account charset when decoding json / xml

        switch( $this->mimeTypeToEncodeType( $contentType ) )
        {
            case 'json':
            case 'application/json':
                $val = json_decode( $stream, true );
                if ( function_exists( 'json_last_error' ) )
                {
                    $err = json_last_error();
                }
                else
                {
                    $err = ( $val === null ) ? 1 : false;
                }
                if ( $err )
                {
                    $this->IsFault = true;
                    $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                    $this->FaultString = ggRESTResponse::INVALIDRESPONSESTRING . ' json. Decoding error: ' . $err;
                }
                else
                {
                    $this->Value = $val;
                }
                break;
            case 'xml':
            case 'text/xml':
            case 'application/xml':
                try
                {
                    $doc = new DOMDocument();
                    $prev = libxml_use_internal_errors( true );
                    libxml_clear_errors();
                    $doc->loadXML( $stream );
                    $node = $doc->documentElement;
                    if ( $node == null )
                    {
                        $errors = libxml_get_errors();
                        libxml_use_internal_errors( $prev );
                        $errorText = '';
                        foreach( $errors as $error)
                        {
                            if ( $error->level > LIBXML_ERR_WARNING )
                            {
                                $errorText .= trim ( $error->message ) . ", line: $error->line" . " column: $error->column\n";
                            }
                        }
                        throw new Exception( $errorText );
                    }
                    libxml_use_internal_errors( $prev );
                    $this->Value = new ggSimpleTemplateXML( $node );

                }
                catch ( Exception $e )
                {
                    $this->IsFault = true;
                    $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                    $this->FaultString = ggRESTResponse::INVALIDRESPONSESTRING . ' xml. ' . $e->getMessage();
                }
                break;
            case 'text/plain':
            case 'text/html':
                $this->Value = $stream;
                break;
            default:
                $this->IsFault = true;
                $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                $this->FaultString = ggRESTResponse::INVALIDRESPONSESTRING . " (unsupported format $contentType)";
        }
    }

    /// @todo (!important) we could filter here accepted types instead of in payload()
    function setContentType( $type )
    {
        $this->ContentType = $type;
    }

    function setJsonpCallback( $callback )
    {
        $this->JsonpCallback = $callback;
    }

    function jsonpCallback()
    {
        return $this->JsonpCallback;
    }

    static function knownContentTypes()
    {
        return array_unique( self::$KnownContentTypes );
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

    protected $defaultContentType = 'application/json';
    protected $JsonpCallback = false;

    protected static $KnownContentTypes = array(
        'application/json' => 'application/json',
        'text/xml' => 'text/xml',
        'application/xml' => 'application/xml'
    );

    protected $Headers = array( 'Vary' => 'Accept' );
}

?>