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

    public $rawResponse = null;
}

?>