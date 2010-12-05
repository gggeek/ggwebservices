<?php
/**
 * Class used to wrap REST responses (plain, unknown xml). Modeled after Soap equivalent.
 * Needs the simplexml extension
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggRESTResponse extends ggWebservicesResponse
{

    const INVALIDRESPONSESTRING = 'Response received from server is not valid';

    /**
      Returns the payload for the response.
      Encoding varies depending on what the request asked for
    */
    function payload( $contentType='json' )
    {
        if ( $this->IsFault )
        {
            // default representation of an error, hand picked
            $value = array( 'faultCode' => $this->FaultCode, 'faultString' => $this->FaultString );
            // send an HTTP error code, since there is no other way to make sure
            // that the client can tell apart error responses from valid array responses
            header( 'HTTP/1.1 500 Internal Server Error' );
        }
        else
        {
            $value = $this->Value;
        }
        switch( $contentType )
        {
            case 'php':
                return var_export( $this->Value );
            case 'phps':
                return serialize( $this->Value );
            case 'json':
                return json_encode( $this->Value, JSON_FORCE_OBJECT );
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
        // save raw data for debugging purposes
        $this->rawResponse = $stream;

        // save raw data for debugging purposes
        $this->rawResponse = $stream;

        if ( $headers === false )
        {
            $stream = self::stripHTTPHeader( $stream );
        }

        /// @todo... parse http headers in $stream for Content-Type header field,
        ///          then decode it to actual types supported
        $contentType = '';

        switch( $contentType )
        {
            case 'json':
                $val = json_decode( $data, true );
                if ( $err = json_last_error() )
                {
                    $this->IsFault = true;
                    $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                    $this->Faulstring = ggRESTResponse::INVALIDRESPONSESTRING . ' json. Decoding error: ' . $err;
                }
                else
                {
                    $this->Value = $val;
                }
                break;
            case 'xml':
                try
                {
                    $xml = new SimpleXMLElement( $data );
                    $this->IsFault = false;
                    $this->FaultString = false;
                    $this->FaultCode = false;
                    $this->Value = $xml;
                }
                catch ( Exception $e )
                {
                    $this->IsFault = true;
                    $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                    $this->Faulstring = ggRESTResponse::INVALIDRESPONSESTRING . ' xml. ' .$e->getMessage();
                }
                break;
            default:
                $this->IsFault = true;
                $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
                $this->Faulstring = ggRESTResponse::INVALIDRESPONSESTRING . " (unsupported format $contentType)";
        }
    }

}

?>