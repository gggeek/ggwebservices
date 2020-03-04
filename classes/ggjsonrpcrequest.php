<?php
/**
 * Class used to wrap jsonrpc requests. Needs the json extension
 *
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 */

class ggJSONRPCRequest extends ggWebservicesRequest
{
    function __construct( $name='', $parameters=array(), $id=1 )
    {
        // strip out param names, since jsonrpc only uses positional params
        parent::__construct( $name, array_values( $parameters ) );
        $this->Id = $id;
    }

    function addParameter( $name, $value )
    {
        $this->Parameters[] = $value;
    }

    function decodeStream( $rawResponse )
    {
        $resp = json_decode( $rawResponse, true );

        if ( is_array( $resp ) && array_key_exists( 'method', $resp ) && array_key_exists( 'params', $resp )
            && is_string( $resp['method'] ) && is_array( $resp['params'] ) )
        {
            $this->Name = $resp['method'];
            $this->Parameters = $resp['params'];
            if ( array_key_exists( 'id', $resp ) )
            {
                $this->Id = $resp['id'];
            }
            else
            {
                $this->Id = null;
            }
            return true;
        }
        else
          return false;
    }

    function payload()
    {
        return json_encode( array( 'method' => $this->Name, 'params' => $this->Parameters, 'id' => $this->Id ) );
    }

    function id()
    {
        return $this->Id;
    }

    protected $Id;

    protected $Verb = 'POST';
    protected $ContentType = 'application/json';
}
