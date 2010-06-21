<?php
/**
 * Class used to communicate with jsonrpc servers
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggJSONRPCClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->ResponseClass = 'ggJSONRPCResponse';
        $this->UserAgent = 'gg eZ JSONRPC client';
        $this->ContentType = 'application/json';
        parent::__construct( $server, $path, $port, $protocol );
    }

    /**
      Sends a jsonrpc message and returns the response object.
    */
    function send( $request )
    {
        $response = parent::send( $request );
        if ( is_object( $response ) )
        {
            // we need to set the response name into the response, since for JSONRPC calls there is no call name in response
        }
        return $response;
    }
}

?>