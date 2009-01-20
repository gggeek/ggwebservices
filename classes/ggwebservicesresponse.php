<?php
/**
 * Generic lass used to wrap webservices responses. Modeled after Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 */

abstract class ggWebservicesResponse
{

    const INVALIDREQUESTERROR = -1;
    const INVALIDRESPONSEERROR = -2;
    const GENERICRESPONSEERROR = -3;
    const INVALIDMETHODERROR = -4;
    const INVALIDPARAMSERROR = -5;
    /// @todo use a single array for all error strings
    const INVALIDREQUESTSTRING = 'Request received from client is not valid according to protocol format';
    const INVALIDRESPONSESTRING = 'Response received from server is not valid';
    const GENERICRESPONSESTRING = 'Server error';
    const INVALIDMETHODSTRING = 'Method not found';
    const INVALIDPARAMSSTRING = 'Parameters not matching method';

    function __construct( $name='' )
    {
        $this->Name = $name;
    }

    /**
      Returns the payload for the response.
      Uses internal members name, value, isFault, faultString and faultCode
    */
    abstract function payload();

    /**
    * Decodes the response to a text stream.
    * Sets internal members value, isFault, faultString and faultCode
    * Name is not set to response from request - a bit weird...
    */
    abstract function decodeStream( $request, $stream );

    /**
    * This function imported from php-xmlrpc lib: it is more correct than eZ SOAP client one
    */
    static function stripHTTPHeader( $data )
    {
        // Look for CR/LF or simple LF as line separator,
        // (even though it is not valid http)
        $pos = strpos($data,"\r\n\r\n");
        if( $pos || is_int( $pos ) )
        {
            $bd = $pos+4;
        }
        else
        {
            $pos = strpos( $data, "\n\n" );
            if( $pos || is_int( $pos ) )
            {
                $bd = $pos+2;
            }
            else
            {
                // No separation between response headers and body: fault?
                $bd = 0;
            }
        }
        if ($bd)
        {
            // this filters out all http headers from proxy.
            // maybe we could take them into account, too?
            return substr( $data, $bd );
        }
        else
        {
            //error_log('XML-RPC: xmlrpcmsg::parseResponse: HTTPS via proxy error, tunnel connection possibly failed');
            //$r = new xmlrpcresp(0, $GLOBALS['xmlrpcerr']['http_error'], $GLOBALS['xmlrpcstr']['http_error']. ' (HTTPS via proxy error, tunnel connection possibly failed)');
            return $data;
        }
    }

    /**
     Returns true if the response was a fault
    */
    function isFault()
    {
        return $this->IsFault;
    }

    /**
     Returns the fault code
    */
    function faultCode()
    {
        return $this->FaultCode;
    }

    /**
     Returns the fault string
    */
    function faultString()
    {
        return $this->FaultString;
    }

    /**
      Returns the response value as plain php value
    */
    function value()
    {
        return $this->Value;
    }

    /**
     Sets the value of the response (plain php value).
     If $values is (subclass of) ggWebservicesFault, sets the response to error state.
    */
    function setValue( $value )
    {
        $this->Value = $value;
        if ( $value instanceof ggWebservicesFault )
        {
            $this->IsFault = true;
            $this->FaultCode = $value->faultCode();
            $this->FaultString = $value->faultString();
        }
        else
        {
            $this->IsFault = false;
        }
    }


    /// Contains the response value
    protected $Value = false;
    /// Contains fault string
    protected $FaultString = false;
    /// Contains the fault code
    protected $FaultCode = false;
    /// Contains true if the response was an fault
    protected $IsFault = false;
    /// Contains the name of the response, i.e. function call name
    protected $Name;

}

?>