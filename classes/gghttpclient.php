<?php
/**
 * Class used to communicate with soap servers
 *
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggHTTPClient extends ggWebservicesClient
{

    function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->ResponseClass = 'ggHTTPResponse';
        $this->UserAgent = 'gg eZ HTTP client';
        parent::__construct( $server, $path, $port, $protocol );
    }

    /**
    * Returns 0 on error, a string if all ok
    */
    static function get( $url )
    {
        // we send an empty params array to the http request, this will make for an empty payload
        return ggHTTPClient::call( $url, array(), 'GET' );
    }

    static function post( $url, $values )
    {
        return ggHTTPClient::call( $url, $values, 'POST' );
    }

    protected static function call( $url, $values, $method )
    {
        $url = parse_url( $url );
        $url = array_merge( array( 'path' => '/', 'port' => 80, 'scheme' => null, 'user' => '', 'pass' => ''), $url );

        // we do not add params to the http request, this will make for an empty payload
        $request = new ggHTTPRequest( '', $values );
        /// @todo are we loosing out fragment, query? ...
        $client = new ggHTTPClient( $url['host'], $url['path'], $url['port'], $url['scheme'] );
        if ( $url['user'] != '' )
        {
            $client->setLogin( $url['user'] );
            $client->setPassword( $url['path'] );
        }
        $client->setMethod( $method );
        $response = $client->send( $request );
        if ( !is_object( $response ) )
        {
            /// @todo should we raise an exception?
            return 0;
        }
        else
        {
            return $response->Value();
        }
    }

}

?>