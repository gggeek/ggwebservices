<?php
/**
 * Class used to communicate with jsonrpc servers
 * @deprecated use a plain ggWebservicesClient instead of this
 *
 * @author G. Giunta
 * @copyright (C) 2009-2022 G. Giunta
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
}
