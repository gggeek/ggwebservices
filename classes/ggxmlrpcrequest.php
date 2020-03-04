<?php
/**
 * Class used to wrap XMLRPC requests. Needs the xmlrpc php extension
 *
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 */

class ggXMLRPCRequest extends ggWebservicesRequest
{
    function __construct( $name='', $parameters=array() )
    {
        // strip out param names, since xmlrpc only uses positional params
        parent::__construct( $name, array_values( $parameters ) );
    }

    function addParameter( $name, $value )
    {
        $this->Parameters[] = $value;
    }

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

    protected $Verb = 'POST';
    protected $ContentType = 'text/xml';
}
