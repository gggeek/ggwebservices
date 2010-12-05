<?php
/**
 * Class used to wrap soap requests.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 *
 * @todo this class could be replaced with a subclass of ggsoaprequest:
 *       - when used by a ggphpsoapclient, only its methods name(), parameters() and ns() are used anyway
 *       - left as it is now, it cannot be used by a ggsoapclient nor by a ggwebservicesclient
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