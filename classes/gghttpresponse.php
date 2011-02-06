<?php
/**
 * Class used to wrap http responses.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2011
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
    */
    function decodeStream( $request, $stream, $headers = false )
    {
	    $this->Value = $stream;
    }
}

?>