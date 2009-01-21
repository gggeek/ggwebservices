<?php
/**
 * Class used to wrap XMLRPC requests. Needs the xmlrpc php extension
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggXMLRPCRequest extends ggWebservicesRequest
{
    /*function __construct( $name='', $parameters=array() )
    {
        parent::__construct( $name, $parameters );
    }*/

    function decodeStream( $rawResponse )
    {
        /// @todo test if this automatically groks encoding from xml or not...
        $meth = '';
        $resp = xmlrpc_decode_request( $rawResponse, $meth );
        if ( !is_array( $resp ) )
        {
            return false;
        }

        $this->Name = $meth;
        $this->Parameters = $resp;
        return true;
    }

    function payload()
    {
        //return xmlrpc_encode_request( $this->Name, $this->Parameters, array( 'verbosity'=> 'newlines_only', 'escaping' => 'markup', 'encoding' => 'utf-8' ) ) );
        return xmlrpc_encode_request( $this->Name, $this->Parameters, array( 'verbosity'=> 'newlines_only', 'encoding' => 'utf-8' ) );
    }

}

?>