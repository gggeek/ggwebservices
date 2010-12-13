<?php
/**
 *
 * @author G. Giunta
 * @version $Id: ggjsonrpcserver.php 199 2010-12-04 00:06:40Z gg $
 * @copyright (C) G. Giunta 2010
 *
 * @todo implement validateparams
 */

class ggRESTServer extends ggWebservicesServer
{

    function prepareResponse( $response )
    {
        $response->setContentType( $this->ResponseType );
        $response->setJsonpCallback( $this->JsonpCallback );
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
            $this->JsonpCallback = $request->jsonpCallback();
            return $request;
        }
        else
        {
            $this->ResponseType = '';
            return false;
        }
    }

    /**
    * Rest protocol uses named parameters, hence the calling convention is different:
    * the php functions are expected to receive an array as the only param, containing
    * in it all actual parameters with their names.
    */
    function handleRequest( $functionName, $params )
    {
        return parent::handleRequest( $functionName, array( $params ) );
    }

    protected $ResponseType = '';
    protected $ResponseClass = 'ggRESTResponse';
    protected $JsonpCallback = false;
}

?>