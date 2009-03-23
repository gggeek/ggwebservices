<?php
/**
 * Class used to wrap rest requests.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggRESTRequest extends ggWebservicesRequest
{
    /**
    * Payload is part of url that is built REST style: /method?p1=val1&p2=val2
    */
    function payload()
    {
        $return  = '/' . $this->Name;
        if ( count( $this->Parameters ) )
        {
            $return .= '?';
            foreach( $this->Parameters as $key => $val )
            {
                $return .= urlencode( $key ) . '=' . urlencode( $val ) . '&';
            }
             $return = substr( $return, 0, -1 );
        }
        return $return;
    }

    /// unlike other requests, $rawRequest here should be the incoming url, not ths post data
    function decodeStream( $rawRequest )
    {
        /// @todo... split on & then on = and urldecode
    }
}

?>