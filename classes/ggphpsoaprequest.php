<?php
/**
 * Class used to wrap soap requests. Code copy'n'pasted from eZ SOAP class
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggPhpSOAPRequest extends ggWebservicesRequest
{
    /**
    * @see ggSOAPRequest::__construct
    */
    function __construct( $name='', $parameters=array(), $namespace=null )
    {
        parent::__construct( $name, $parameters );
        $this->ns = $namespace;
    }

    function payload()
    {
        /// @todo throw exception!
        return '';
    }

    function decodeStream( $rawRequest )
    {
        /// @todo throw exception!
        return false;
    }

    function ns()
    {
        return $this->ns;
    }

    protected $ns;

}

?>