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

    const INVALIDRESPONSESTRING = 'Response received from server is not valid xml';

    /**
      Returns the json payload for the response.
      @todo
    */
    function payload()
    {
        /*
        if ( $this->IsFault )
        {
            return json_encode( array(
                'result' => $this->Value,
                'error' => null,
                'id' => $this->Id ) );
        }
        else
        {
            return json_encode( array(
                'result' => null,
                'error' => array( 'faultCode' => $this->FaultCode, 'faultString' => $this->FaultString ),
                'id' => $this->Id ) );
        }*/
        return 'payload method for REST responses is still to be implemented!!!';
    }

    /**
    * Decodes the REST response stream.
    * Request is not used, kept for compat with sibling classes
    * Name is not set to response from request - a bit weird...
    */
    function decodeStream( $request, $stream )
    {
        // save raw data for debugging purposes
        $this->rawResponse = $stream;

        try
        {
            $xml = new SimpleXMLElement( ggWebservicesResponse::stripHTTPHeader( $stream ) );
            $this->IsFault = false;
            $this->FaultString = false;
            $this->FaultCode = false;
            $this->Value = $xml;
        }
        catch ( Exception $e )
        {
            $this->IsFault = true;
            $this->FaultCode = ggRESTResponse::INVALIDRESPONSEERROR;
            $this->Faulstring = ggRESTResponse::INVALIDRESPONSESTRING . $e->getMessage();
        }
    }

    public $rawResponse = null;
}

?>