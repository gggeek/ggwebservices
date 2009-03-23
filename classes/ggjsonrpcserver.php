<?php
/**
 * Extends ggXMLRPCServer instead of ggWebservicesServer to profit of handling
 * of system.xx methods
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 * @see http://json-rpc.org/wiki/specification
 */
class ggJSONRPCServer extends ggXMLRPCServer
{

    /**
    * Echoes the response, setting http headers and such
    */
    function showResponse( $functionName, $namespaceURI, &$value )
    {

        $response = new ggJSONRPCResponse( $functionName );
        $response->setValue( $value );
        /// @todo we need to reinject the request id into the response
        $response->setId( $this->Id );
        $payload = $response->payload();

        //header( "SOAPServer: eZ soap" );
        header( "Content-Type: application/json; charset=\"UTF-8\"" );
        header( "Content-Length: " . strlen( $payload ) );

        if ( ob_get_length() )
            ob_end_clean();

        print( $payload );
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

}

?>