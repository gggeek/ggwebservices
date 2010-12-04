<?php
/**
 * Class used to wrap http requests.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggHTTPRequest extends ggWebservicesRequest
{

    function decodeStream( $rawRequest )
    {
        /// @todo... split on & then on = and urldecode?
    }

    /**
    * this payload can be used in URIS for GETs and in http req bodies for POSTs
    * @todo add support for array params?
    */
    function payload()
    {
        $results = array();
        foreach( $this->Parameters as $key => $val )
        {
            $results[] = urlencode($key) . '=' . urlencode($val);
        }
        return implode( '&', $results );
    }

}

?>