<?php
/**
 * Class used to wrap soap responses. Modeled after the eZ Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggPhpSOAPResponse extends ggWebservicesResponse
{

    function payload( )
    {
        /// @todo throw exception
        return '';
    }

    /**
    * Differs from parent's version in that it only saves $stream for debugging
    * purposes!
    */
    function decodeStream( $request, $stream, $headers=false )
    {
        // save raw data for debugging purposes
        $this->rawResponse = $stream;
    }

    public $rawResponse = null;
}

?>