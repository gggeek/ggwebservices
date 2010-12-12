<?php
/**
 * Generic class used to wrap webservices requests. Modeled after eZP Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 *
 */

abstract class ggWebservicesRequest
{

    function __construct( $name='', $parameters=array() )
    {
        $this->Name = (string)$name;
        $this->Parameters = $parameters;
        /// @todo check: is this used anywhere? if not, remove it!
        //$this->ContentType = 'application/x-www-form-urlencoded';
    }

    /**
    * Returns the request payload encoded according to its specific protocol
    * (in HTTP terms, the 'message body')
    * @return string
    */
    abstract function payload();

    /**
    * Used by servers when decoding incoming requests
    * @param string $rawRequest
    * @return bool false if received data cannot be parsed back into a valide request
    */
    abstract function decodeStream( $rawRequest );

    /**
    * Returns the final URI that will be used by the client, based on its initial
    * version (except the protocol://host:port part).
    * Useful for protocols that encode parameters in the URL and use GET instead
    * of POST.
    * Override it in case of need.
    */
    function requestURI( $uri )
    {
        return $uri;
    }

    /**
      Returns the request name.
    */
    function name()
    {
        return $this->Name;
    }

    function addParameter( $name, $value )
    {
        $this->Parameters[$name] = $value;
    }

    function addParameters( $params )
    {
        foreach( $params as $name => $val )
        $this->addParameter( $name, $value );
    }

    function parameters()
    {
        return $this->Parameters;
    }

    function method()
    {
        return strtoupper( $this->Verb );
    }

    function contentType()
    {
        return $this->ContentType;
    }

    function requestHeaders()
    {
        return array();
    }

    /// Contains the request parameters
    protected $Parameters = array();

    protected $Name;

    protected $Verb = '';

    protected $ContentType = '';
}

?>