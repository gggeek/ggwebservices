<?php
/**
 * Class used to communicate with 'REST' servers
 * @deprecated use a plain ggWebservicesClient instead of this
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
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
}

?>