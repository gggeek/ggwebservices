<?php
/**
 * generic WebServices fault class. Code taken from eZ Soap Fault (api is the same)
 *
 * @author G. Giunta
 * @copyright (C) 2009-2016 G. Giunta
 */

class  ggWebservicesFault
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

    /**
    * This is called when an error is converted to plain text (without indication
    * of the expected text format), such as eg. by ezjscore "call" view
    */
    function __tostring()
    {
        return $this->FaultCode . ': "' . $this->FaultString . '"';
    }

    /// Contains the fault code
    protected $FaultCode;

    /// Contains the fault string
    protected $FaultString;
}

?>
