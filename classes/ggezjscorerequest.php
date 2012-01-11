<?php
/**
 * Class used to wrap ezjscore requests. Needs the json extension
 *
 * @author G. Giunta
 * @version $Id: ggjsonrpcrequest.php 290 2011-02-06 17:36:55Z gg $
 * @copyright (C) 2011-2012 G. Giunta
 */

class ggeZJSCoreRequest extends ggWebservicesRequest
{

    /// @todo implement this
    function decodeStream( $rawResponse )
    {
        return false;
    }

    /// urlencoding of params for request body
    function payload()
    {
        $results = array();
        foreach( $this->Parameters as $key => $val )
        {
            if ( is_array( $val ) )
            {
                foreach ( $val as $vkey => $vval )
                {
                    $results[] = urlencode( $key ) . '[' . urlencode( $vkey ) . ']=' . urlencode( $vval );
                }
            }
            else
            {
                $results[] = urlencode( $key ) . '=' . urlencode( $val );
            }
        }
        return implode( '&', $results );
    }

    /**
     * Final part of url that is built ezjscore style: /Class::Method
     */
    function requestURI( $uri )
    {
        $parsed = parse_url( $uri );

        $return = '';
        if ( isset( $parsed['user'] ) )
        {
            $return .= $parsed['user'] . '@' . $parsed['pass'];
        }
        $return .= rtrim( $parsed['path'], '/' ) . '/' . $this->Name;

        if ( isset( $parsed['query'] ) )
        {
            $return  .= '?' . $parsed['query'];
        }
        if ( isset( $parsed['fragment'] ) )
        {
            $return .= '#' . $parsed['fragment'];
        }

        return $return;
    }

    function requestHeaders()
    {
        return array( 'Accept' => 'application/json; q=0.8, text/xml; q=0.5' );
    }

    protected $Verb = 'POST';
    protected $ContentType = 'application/x-www-form-urlencoded';

}

?>