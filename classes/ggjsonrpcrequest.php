<?php
/**
 * Class used to wrap jsonrpc requests. Needs the json extension
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggJSONRPCRequest extends ggWebservicesRequest
{
    function __construct( $name='', $parameters=array(), $id=null )
    {
        parent::__construct( $name, $parameters );
        $this->Id = $id;
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

}

?>