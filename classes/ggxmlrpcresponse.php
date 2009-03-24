<?php
/**
 * Class used to wrap XMLRPC responses. Modeled after Soap equivalent.
 * Needs the xmlrpc php extension
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggXMLRPCResponse extends ggWebservicesResponse
{

    const INVALIDRESPONSESTRING = 'Response received from server is not valid XMLRPC';

    /**
      Returns the xmlrpc payload for the response.
    */
    function payload()
    {
        if ( $this->IsFault )
        {
            $payload = xmlrpc_encode( array(
                "faultCode" => $this->FaultCode,
                "faultString" => $this->FaultString ) );
            return str_replace( array( '<fault>', '</fault>' ), array( '<methodResponse><fault>', '</fault></methodResponse>' ), $payload );
        }
        else
        {
            $payload = xmlrpc_encode( $this->Value );
            /// @todo verify if user gave us back an array with faultString / faultCode members,
            ///       as we will be sending back junk in that case...
            return str_replace( array( '<params>', '</params>' ), array( '<methodResponse><params>', '</params></methodResponse>' ), $payload );
        }
    }

    /**
    * Decodes the XMLRPC response stream.
    * Name is not set to response from request - a bit weird...
    */
    function decodeStream( $request, $stream )
    {
        // save raw data for debugging purposes
        $this->rawResponse = $stream;

        /// @todo test if this automatically groks encoding from xml or not...
        $results = xmlrpc_decode( self::stripHTTPHeader( $stream ) );

        if ( $results === null )
        {
            // invalid XMLRPC response
            $this->IsFault = true;
            $this->FaultCode = self::INVALIDRESPONSEERROR;
            $this->Faulstring = self::INVALIDRESPONSESTRING;
        }
        else
        {

            if ( !is_array( $results ) || count( $results ) != 2 || !isset( $results['faultCode'] ) || !isset( $results['faultString'] ) )
            {
                $this->IsFault = false;
                $this->FaultString = false;
                $this->FaultCode = false;
                $this->Value = $results;
            }
            else
            {
                $this->IsFault = true;
                $this->FaultCode = $results['faultCode'];
                $this->FaultString = $results['faultString'];
            }
        }
    }

    public $rawResponse = null;
}

?>