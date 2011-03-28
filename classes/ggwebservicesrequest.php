<?php
/**
 * Generic class used to wrap webservices requests. Modeled after eZP Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2011
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
    * (in HTTP terms, this is the message body).
    * This function is called client-side.
    * @return string
    */
    abstract function payload();

    /**
    * Used by servers when decoding incoming requests from the http request received.
    * Should set the Parameters and all relevant internal members
    * @param string $rawRequest
    * @return bool false if received data cannot be parsed into a valid request
    * @todo this call is too naive, as it discards both http headers and URL,
    *       which might be needed for eg. REST protocolsrequire_once('../../../../../svn/projects.ez.no/ggwebservices/trunk/extension/ggwebservices/classes/ggxmlrpcrequest.php');
    *       We keep it like this for now for backward compat, but it might change later on...
    */
    abstract function decodeStream( $rawRequest );

    /**
    * Returns the final URI that will be used by the client, based on its initial
    * version (except the protocol://host:port part).
    * Useful for protocols that encode parameters in the URL and use GET instead
    * of POST. Default is not to touch the url received and give it back intact.
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

    /**
    * This call should be reversed: addParameter( $value, $name='' ) to better
    * support protocols with positional params. Alas we keep compatibility with
    * the eZP soap request class.
    */
    function addParameter( $name, $value )
    {
        $this->Parameters[$name] = $value;
    }

    function addParameters( $params )
    {
        foreach( $params as $name => $value )
        {
            $this->addParameter( $name, $value );
        }
    }

    function parameters()
    {
        return $this->Parameters;
    }

    /// as in 'http verb'. This is not the method name of the request.
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