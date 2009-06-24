<?php
/**
 * Class used to communicate with soap servers
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggSOAPClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80 )
    {
        $this->ResponseClass = 'ggSOAPResponse';
        $this->UserAgent = 'gg eZ SOAP client';
        $this->ContentType = 'text/xml'; /// @todo add UTF8 charset by default?
        parent::__construct( $server, $path, $port );
    }

    /**
      Sends a soap message and returns the response object.
    */
    function send( $request )
    {
        /// @todo add a check that request is a sooap one, or it will have no namespace method...
        $this->RequestHeaders = array( "SOAPAction" => $request->ns() . '/' . $request->name() );
        $response = parent::send( $request );
        return $response;
    }
}

?>