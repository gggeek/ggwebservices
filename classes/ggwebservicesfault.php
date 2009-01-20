<?php
/**
 * generic WebServices fault class. Code taken from eZ Soap Fault (api is the same)
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class  ggWebServicesFault
{
    /**
     Constructs a new Fault object, settiung code and string
    */
    function __construct( $faultCode = "", $faultString = "" )
    {
        $this->FaultCode = $faultCode;
        $this->FaultString = $faultString;
    }

    /**
     Returns the fault code.
    */
    function faultCode()
    {
        return $this->FaultCode;
    }

    /**
     Returns the fault string.
    */
    function faultString()
    {
        return $this->FaultString;
    }

    /// Contains the fault code
    protected $FaultCode;

    /// Contains the fault string
    protected $FaultString;
}

?>
