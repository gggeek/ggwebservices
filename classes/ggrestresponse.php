<?php
/**
 * Class used to wrap REST responses.
 * Needs the simplexml extension when serializing in xml, the json ext. for json
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
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
                    header( 'HTTP/1.1 404 Not Found' );
                    break;
                case ggWebservicesServer::INVALIDPARAMSERROR:
                case ggWebservicesServer::INVALIDCOMPRESSIONERROR:
                case ggWebservicesServer::INVALIDREQUESTERROR:
                    header( 'HTTP/1.1 400 Bad Request' );
                    break;
                case ggWebservicesServer::INVALIDAUTHERROR:
                    header( 'HTTP/1.1 403 Forbidden' );
                    break;

                default:
                    header( 'HTTP/1.1 500 Internal Server Error' );
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
    */
    function decodeStream( $request, $stream, $headers=false )
    {
        $contentType = isset( $headers['content-type'] ) ? $headers['content-type'] : '';
        if ( ( $pos = strpos( $contentType, ';' ) ) !== false )
        {
            $contentType = substr( $contentType, 0, $pos );
        }

        switch( $contentType )
        {
            case 'application/json':
                $val = json_decode( $stream, true );
                if ( $err = json_last_error() )
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
            case 'text/xml':
            case 'application/xml':
                try
                {
                    $xml = new SimpleXMLElement( $stream );
                    $this->IsFault = false;
                    $this->FaultString = false;
                    $this->FaultCode = false;
                    $this->Value = $xml;
                }
                catch ( Exception $e )
                {
                    $this->IsFault = true;
                    $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                    $this->FaultString = ggRESTResponse::INVALIDRESPONSESTRING . ' xml. ' .$e->getMessage();
                }
                break;
            default:
                $this->IsFault = true;
                $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                $this->FaultString = ggRESTResponse::INVALIDRESPONSESTRING . " (unsupported format $contentType)";
        }
    }

    function responseHeaders()
    {
        return array( 'Vary' => 'Accept' );
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

    protected $defaultContentType = 'application/json';
    protected $JsonpCallback = false;
}

?>