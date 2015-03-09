<?php
/**
 * Class used to wrap soap responses. Modeled after the eZ Soap equivalent.
 *
 * @author G. Giunta
 * @copyright (C) 2009-2015 G. Giunta
 */

class ggPhpSOAPResponse extends ggWebservicesResponse
{

    /// the use done of this function is a bit warped, ie. it does not conform
    /// to parent's class usage. @see ggPhpSOAPClient::_send()
    function payload( )
    {
        return $this->Value;
    }

    /// the use done of this function is a bit warped, ie. it does not conform
    /// to parent's class usage. @see ggPhpSOAPClient::_send()
    function decodeStream( $request, $stream, $headers=false, $cookies=array(), $statuscode="200" )
    {
        /// @todo verify if this makes sense
        $this->decodeStreamCommon( $request, $stream, $headers, $cookies, $statuscode );

        $this->Value = $stream;
    }
}

?>