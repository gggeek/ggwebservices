<?php
/**
 * Class used to communicate with XMLRPC servers
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggXMLRPCClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->ResponseClass = 'ggXMLRPCResponse';
        $this->UserAgent = 'gg XMLRPC client';
        $this->ContentType = 'text/xml';
        parent::__construct( $server, $path, $port, $protocol );
    }

    /**
      Sends a XMLRPC message and returns the response object.
    */
    function send( $request )
    {
        $response = parent::send( $request );
        if ( is_object( $response ) )
        {
            // we need to set the response name into the response, since for XMLRPC calls there is no call name in response
        }
        return $response;
    }
}

?>