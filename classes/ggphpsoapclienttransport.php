<?php
/**
 * A ping-pong class: we take control of the "transport" part of the soap call
 * by routing it back to the ggPhpSOAPClient client itself
 *
 * @version $Id$
 * @copyright (C) 2010-2012 G. Giunta
 */

class ggPhpSOAPClientTransport extends SoapClient {

    // store a pointer to the ggPhpSOAPClient client starting the call
    function __construct( $wsdl, $options, $client=null, $request=null )
    {
        parent::__construct( $wsdl, $options );
        $this->client = $client;
        $this->request = $request;
    }

    function __doRequest( $request, $location, $action, $version, $one_way = 0 )
    {
        // inject the generated XML payload into the original request
        $this->request->decodeStream( $request );
        /// @todo test: what do we get here: 1/2 ?
        $this->request->setSoapVersion( $version );

        /// @todo inject $action into the request name() and ns(), too!

        // and forward it all to the original client for the doing the http call
        return $this->client->_send( $this->request, $location, $action, $version, $one_way );
    }

}

?>