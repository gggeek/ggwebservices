<?php
/**
 * Class used to wrap http requests.
 * Supports many http methods - which are in this case taken from req. Name.
 *
 * @author G. Giunta
 * @copyright (C) 2009-2013 G. Giunta
 */

class ggHTTPRequest extends ggWebservicesRequest
{

    function decodeStream( $rawRequest )
    {
        /// @todo... look at verb, and recover data from either query string or
        ///          form/urlencoded data - or both? also, how to decide what is
        ///          the Name of the request? Is this best left to subclasses?
        return false;
    }

    /**
    * Methods in rfc2166: 'GET', 'POST', 'PUT', 'HEAD', 'OPTIONS', 'DELETE', 'TRACE', 'CONNECT'
    * In theory all of them can accept a req. body, even though in practice
    * body is discarded for GET, and most likely HEAD, OPTIONS, DELETE, TRACE, CONNECT...
    */
    function payload()
    {
        return $this->_payload();
    }

    /**
    * the payload can be used in URIS for GETs and in http req bodies for POSTs
    * @todo add support for array params
    */
    protected function _payload()
    {
        $results = array();
        foreach( $this->Parameters as $key => $val )
        {
            if ( is_array( $val ) )
            {
                foreach ( $val as $vkey => $vval )
                {
                    $results[] = urlencode( $key ) . '[' . urlencode( $vkey ) . ']=' . urlencode( $vval );
                }
            }
            else
            {
                $results[] = urlencode( $key ) . '=' . urlencode( $val );
            }
        }
        return implode( '&', $results );
    }

    function method()
    {
        /// @todo should we test for valid verbs? nb: hard to do, since http is extensible...
        return strtoupper( $this->Name );
    }

    protected $ContentType = 'application/x-www-form-urlencoded';
}

?>