<?php
/**
 * Class used to wrap http responses.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2008
 */

class ggHTTPResponse extends ggWebservicesResponse
{

    /**
      Returns the payload for the response.
    */
    function payload()
    {
        return $this->Value;
    }

    /**
    * Decodes the HTTP response stream.
    * Name is not set to response from request - a bit weird...
    */
    function decodeStream( $request, $stream )
    {
	    // save raw data for debugging purposes
	    $this->rawResponse = $stream;

	    $this->Value = ggWebservicesResponse::stripHTTPHeader( $stream );
    }

    public $rawResponse = null;
}

?>