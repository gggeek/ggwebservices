<?php
/**
 * Class used to communicate with ezjscore servers
 * @deprecated use a plain ggWebservicesClient instead of this
 *
 * @author G. Giunta
 * @copyright (C) 2011-2013 G. Giunta
 */

class ggeZJSCoreClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->ResponseClass = 'ggeZJSCoreResponse';
        $this->UserAgent = 'gg eZJSCore client';
        $this->ContentType = 'application/x-www-form-urlencoded';
        parent::__construct( $server, $path, $port, $protocol );
    }
}

?>