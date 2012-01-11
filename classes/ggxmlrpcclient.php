<?php
/**
 * Class used to communicate with XMLRPC servers
 * @deprecated use a plain ggWebservicesClient instead of this
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) 2009-2012 G. Giunta
 */

class ggXMLRPCClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->ResponseClass = 'ggXMLRPCResponse';
        $this->UserAgent = 'gg eZ XMLRPC client';
        $this->ContentType = 'text/xml';
        parent::__construct( $server, $path, $port, $protocol );
    }
}

?>