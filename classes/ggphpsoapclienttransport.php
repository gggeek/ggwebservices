<?php
/**
 * A ping-pong class: we take control of the "transport" part of the soap call
 * by routing it back to the ggPhpSOAPClient client itself
 *
 * @author G. Giunta
 * @copyright (C) 2010-2020 G. Giunta
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

        // For wsdl case, php will have generated the 'action' for us.
        // We kindly inject back into the request the namespace taken from tha action
        // (unless user wanted really hard a custom one), as it will be later used
        // to build the SOAPAction http header
        if ( $this->request->ns() == '' && ( $p = strpos( $action, $this->request->name() ) ) !== 0 )
        {
            $this->request->setNamespace( rtrim( substr( $action, 0, $p ), '/' ) );
        }

        /// @todo should wealso inject back into request->name data from $action?

        // and forward it all to the original client for the doing the http call
        return $this->client->_send( $this->request, $location, $action, $version, $one_way );
    }

}
