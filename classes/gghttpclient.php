<?php
/**
 * Class used to communicate with plain http servers
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 */

class ggHTTPClient extends ggWebservicesClient
{

    /// @deprecated the parent class ggWebservicesClient can do everything without
    ///             us having to change anything here
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
        return self::call( $url, array(), 'GET' );
    }

    /**
     * Returns 0 on error, a string if all ok
     */
    static function post( $url, $values )
    {
        return self::call( $url, $values, 'POST' );
    }

    protected static function call( $url, $values, $method )
    {
        $url = parse_url( $url );
        $url = array_merge( array( 'path' => '/', 'port' => 80, 'scheme' => null, 'user' => '', 'pass' => ''), $url );

        // if we do not add params to the http request, this will make for an empty payload
        $request = new ggHTTPRequest( $method, $values );
        /// @todo are we loosing out fragment, query? ...
        $client = new ggHTTPClient( $url['host'], $url['path'], $url['port'], $url['scheme'] );
        if ( $url['user'] != '' )
        {
            $client->setLogin( $url['user'] );
            $client->setPassword( $url['path'] );
        }
        //$client->setMethod( $method );
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