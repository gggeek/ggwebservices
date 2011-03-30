<?php
/**
 * Class used to communicate with ezjscore servers
 * @deprecated use a plain ggWebservicesClient instead of this
 *
 * @author G. Giunta
 * @version $Id: ggjsonrpcclient.php 290 2011-02-06 17:36:55Z gg $
 * @copyright (C) G. Giunta 2011
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