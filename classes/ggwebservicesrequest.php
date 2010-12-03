<?php
/**
 * Generic class used to wrap webservices responses. Modeled after Soap equivalent.
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
        $this->ContentType = 'application/x-www-form-urlencoded';
    }

    /**
    * Returns the request payload encoded according to its specific protocol
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

    function parameters()
    {
        return $this->Parameters;
    }

    /// Contains the request parameters
    protected $Parameters = array();

    protected $Name;

}

?>