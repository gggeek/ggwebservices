<?php
/**
 *
 * @author G. Giunta
 * @version $Id: ggjsonrpcserver.php 199 2010-12-04 00:06:40Z gg $
 * @copyright (C) G. Giunta 2010
 *
 * @see http://json-rpc.org/wiki/specification
 */
class ggRESTServer extends ggWebservicesServer
{

    /**
    * Echoes the response, setting http headers and such
    */
    function showResponse( $functionName, $namespaceURI, &$value )
    {

        $response = new ggRESTResponse( $functionName );
        $response->setValue( $value );
        $response->setContentType( $this->ResponseType );

        $payload = $response->payload();

        $contentType = $response->contentType();
        if ( $contentType != '' )
        {
            //$contentType = $this->decodeMimeType( $contentType );
            $charset = $response->charset();
            if ( $charset !=  '' )
            {
                $contentType .= "; charset = \"$charset\"";
            }
            header( "Content-Type: " . $contentType );
        }

        header( "Content-Length: " . strlen( $payload ) );

        /// @todo add support for a series of response http headers

        if ( ob_get_length() )
            ob_end_clean();

        print( $payload );
    }

    /**
      Processes the request and returns a request object (or false).
    */
    function parseRequest( $payload )
    {
        $request = new ggRESTRequest();
        if ( $request->decodeStream( $payload ) )
        {
            $this->ResponseType = $request->responseType();
            return $request;
        }
        else
        {
            $this->ResponseType = '';
            return false;
        }
    }

    /*function decodeMimeType( $type)
    {
        switch( $type )
        {
            case 'json':
                return 'application/json';
            case 'xml':
                return 'application/xml';
            // not really standard, but found seraching google
            case 'php':
                return 'application/x-httpd-php';
            case 'phps':
                return 'application/vnd.php.serialized';
            default:
                return $type;
        }
    }*/

    protected $ResponseType = '';
}

?>