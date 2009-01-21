<?php
/**
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggXMLRPCServer extends ggWebservicesServer
{

    /**
    * Echoes the response, setting http headers and such
    */
    function showResponse( $functionName, $namespaceURI, &$value )
    {

        $response = new ggXMLRPCResponse( $functionName );
        $response->setValue( $value );
        $payload = $response->payload();

        //header( "SOAPServer: eZ soap" );
        header( "Content-Type: text/xml; charset=\"UTF-8\"" );
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
        $request = new ggXMLRPCRequest();
        if ( $request->decodeStream( $payload ) )
        {
            return $request;
        }
        else
        {
            return false;
        }
    }

}

?>