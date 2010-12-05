<?php
/**
 * Class used to wrap 'REST' requests.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggRESTRequest extends ggWebservicesRequest
{
    /**
    * No request body by default
    */
    function payload()
    {
        return '';
    }

    /**
    * Final part of url that is built REST style: /method?p1=val1&p2=val2
    */
    function queryString()
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

    /**
    * Unlike other requests, $rawRequest here is not used, as GET params are used, not POST.
    * This is a small break of the encapsulation principle of the API, but is
    * faster than having to push this into a specific server class
    */
    function decodeStream( $rawRequest )
    {
        /// @todo... recover method name from the last fragment in the URL
        $this->Parameters = $_GET;
    }

    /// New method in this subclass
    function requestHeaders()
    {
        /// shall we declare support for insecure stuff such as php and serialized php?
        /// NB: this must be accompanied by code that can decode the format in ggRESTResponse
        return array( 'Accept' => 'application/json, text/xml; q=0.5' );
    }

    protected $Verb = 'GET';
}

?>