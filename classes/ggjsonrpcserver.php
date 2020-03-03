<?php
/**
 * Extends ggXMLRPCServer instead of ggWebservicesServer to profit of handling
 * of system.xx methods
 *
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 *
 * @see http://json-rpc.org/wiki/specification
 */

class ggJSONRPCServer extends ggXMLRPCServer
{

    function prepareResponse( $response )
    {
        $response->setId( $this->Id );
    }

    /**
      Processes the request and returns a request object (or false).
    */
    function parseRequest( $payload )
    {
        $request = new ggJSONRPCRequest();
        if ( $request->decodeStream( $payload ) )
        {
            $this->Id = $request->id();
            return $request;
        }
        else
        {
            return false;
        }
    }

    /**
    * allow some type definitions that are more typical of xmlrpc than json, for interop
    * @todo improve validation of object vs. array params
    */
    var $typeMap = array(
        'string'  => array( 'string' ),
        'integer' => array( 'i4', 'int', 'integer', 'number' ),
        'double'  => array( 'double', 'float', 'number' ),
        'boolean' => array( 'bool', 'boolean' ),
        'array'   => array( 'array', 'struct', 'object' ),
        'NULL'    => array( 'null' )
    );

    var $Id;
    protected $ResponseClass = 'ggJSONRPCResponse';
}
