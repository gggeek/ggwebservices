<?php
/**
 * Class used to wrap soap requests, for use with the ggPhpSOAPClient.
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 *
 * @todo this class could be replaced with a subclass of ggsoaprequest:
 *       - when used by a ggphpsoapclient, only its methods name(), parameters() and ns() are used anyway
 *       - left as it is now, it cannot be used by a ggsoapclient nor by a ggwebservicesclient
 */

class ggPhpSOAPRequest extends ggSOAPRequest
{

    function payload()
    {
        return $this->_payload;
    }

    /**
    * This function is called once on the client, not on the server:
    * we leave it up to the php soap lib to generate the payload, then inject it
    * into the request, and route it through another send() call
    */
    function decodeStream( $rawRequest )
    {
        $this->_payload = $rawRequest;
        // we return false, in case some freak server tries to call this function anyway
        return false;
    }

    protected $_payload = '';
}

?>