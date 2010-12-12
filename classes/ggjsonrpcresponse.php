<?php
/**
 * Class used to wrap jsonrpc responses. Modeled after Soap equivalent.
 * Needs the json extension
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggJSONRPCResponse extends ggWebservicesResponse
{

    const INVALIDIDERROR = -350;
    const INVALIDRESPONSESTRING = 'Response received from server is not valid jsonrpc';
    const INVALIDIDSTRING = 'Response received from server does not match request id';


    /**
      Returns the json payload for the response.
    */
    function payload()
    {
        if ( $this->IsFault )
        {
            return json_encode( array(
                'result' => null,
                'error' => array( 'faultCode' => $this->FaultCode, 'faultString' => $this->FaultString ),
                'id' => $this->Id ) );
        }
        else
        {
            return json_encode( array(
                'result' => $this->Value,
                'error' => null,
                'id' => $this->Id ) );
        }
    }

    /**
    * Decodes the JSONRPC response stream.
    * Request is used for matching id.
    * @todo Name is not set to response from request - a bit weird...
    */
    function decodeStream( $request, $stream, $headers = false  )
    {
        // save raw data for debugging purposes
        $this->rawResponse = $stream;

        if ( $headers === false )
        {
            $stream = self::stripHTTPHeader( $stream );
        }

        $results = json_decode( $stream, true );
        if ( !is_array($results) ||
            !array_key_exists( 'result', $results ) ||
            !array_key_exists( 'error', $results ) ||
            !array_key_exists( 'id', $results )
            )
        {
            // invalid jsonrpc response
            $this->IsFault = true;
            $this->FaultCode = self::INVALIDRESPONSEERROR;
            $this->FaultString = self::INVALIDRESPONSESTRING;
        }
        else
        {
            $this->Id =  $results['id'];

            /// we should check if id of response is same as id of request, and raise error if not
            if ( $results['id'] != $request->id() )
            {
                $this->IsFault = true;
                $this->FaultCode = self::INVALIDIDERROR;
                $this->FaultString = self::INVALIDIDSTRING;
            }
            else if ( $results['error'] === null )
            {
                /// NB: spec is formally strange: what if both error and result are null???

                $this->IsFault = false;
                $this->FaultString = false;
                $this->FaultCode = false;
                $this->Value = $results['result'];
            }
            else
            {
                $this->IsFault = true;
                $error = $results['error'];
                if ( is_array( $error ) && array_key_exists( 'faultCode', $error ) && array_key_exists( 'faultString', $error ) )
                {
                    // since server conforms to our error syntax, we force the types on him, too
                    $this->FaultCode = (int)$error['faultCode'];
                    $this->FaultString = (string)$error['faultString'];
                }
                else
                {
                    $this->FaultCode = self::GENERICRESPONSEERROR;
                    /// @todo we should somehow typecast to string here??? maybe reencode as json?
                    $this->FaultString = $results['error'];
                }
            }
        }
    }

    /**
     Sets the id of the response (plain php value)
    */
    function setId( $id )
    {
        $this->Id = $id;
    }

    protected $Id;
    protected $ContentType = 'application/json';
}

?>