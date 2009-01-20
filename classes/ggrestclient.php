<?php
/**
 * Class used to communicate with jsonrpc servers
 *
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggRESTClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80, $protocol = null )
    {
        $this->ResponseClass = 'ggRESTResponse';
        $this->UserAgent = 'gg eZ REST client';
        $this->Verb = 'GET';
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
            // we need to set the response name into the response, since for REST calls there is no call name in response
        }
        return $response;
    }
}

?>