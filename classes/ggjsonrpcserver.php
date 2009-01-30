<?php

class ggJSONRPCServer extends ggWebservicesServer
{

    /**
    * Echoes the response, setting http headers and such
    */
    function showResponse( $functionName, $namespaceURI, &$value )
    {

        $response = new ggJSONRPCResponse( $functionName );
        $response->setValue( $value );
        /// @todo we need to reinject the request id into the response
        $response->setId( $this->Id );
        $payload = $response->payload();

        //header( "SOAPServer: eZ soap" );
        header( "Content-Type: application/json; charset=\"UTF-8\"" );
        header( "Content-Length: " . strlen( $payload ) );

        if ( ob_get_length() )
            ob_end_clean();

        print( $payload );
    }

    /**
      Processes the request and returns a request object (or false).
    */
    function parseRequest( $payload )
    {
        $request = new ggJSONRPCRequest();
        if ( $request->decodeStream( $payload ) )
        {
            $this->Id = $request->id();
            return $request;
        }
        else
        {
            return false;
        }
    }

    function isInternalRequest( $functionName )
    {
        return in_array( $functionName, $this->internalMethods );
    }

    function handleInternalRequest( $functionName, $params )
    {
        switch( $functionName )
        {
            case 'system.listMethods':
            case 'system.methodSignature':
            case 'system.methodHelp':
            case 'system.multicall':
                $server = new ggXMLRPCServer( '' );
                return $server->handleInternalRequest( $functionName, $params, $this );
            default:
                return parent::handleInternalRequest( $functionName, $params );
        }
    }

    var $Id;

    /// Courtesy: internal methods list includes those of xmlrpc standard
    var $internalMethods = array(
        //'system.getCapabilities',
        'system.listMethods',
        'system.methodSignature',
        'system.methodHelp',
        'system.multicall' );
}

?>