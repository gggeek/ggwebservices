<?php
/**
 * Generic class used to wrap webservices responses. Modeled after Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2008
 *
 */

abstract class ggWebservicesRequest
{

    function __construct( $name='', $parameters=array() )
    {
        $this->Name = (string)$name;
        $this->Parameters = $parameters;
        $this->ContentType = 'application/x-www-form-urlencoded';
    }

    abstract function payload();

    /// Used by servers when decoding incpming requests
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