<?php
/**
 * Class used to wrap http responses.
 *
 * @author G. Giunta
 * @copyright (C) 2009-2022 G. Giunta
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
    function decodeStream( $request, $stream, $headers=false, $cookies=array(), $statuscode="200" )
    {
        $this->decodeStreamCommon( $request, $stream, $headers, $cookies, $statuscode );

        $this->Value = $stream;
    }
}
