<?php
/**
 * Generic class used to wrap webservices responses. Modeled after eZP Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2011
 */

abstract class ggWebservicesResponse
{
    const INVALIDRESPONSEERROR = -301;
    const GENERICRESPONSEERROR = -302;

    /// @todo use a single array for all error strings
    const INVALIDRESPONSESTRING = 'Response received from server is not valid';
    const GENERICRESPONSESTRING = 'Server error';


    function __construct( $name='' )
    {
        $this->Name = $name;
    }

    /**
      Returns the payload for the response (in HTTP terms, the 'message body').
      Uses internal members name, value, isFault, faultString and faultCode
      @return string
    */
    abstract function payload();

    /**
    * Decodes the response from a text stream (usually the http response body).
    * Sets internal members value, isFault, faultString and faultCode
    * Name is not set to response from request - a bit weird... but client injects name using the request one anyway
    * The API is a bit whacky because we want too keep compat with the eZP original version
    * @param ggWebservicesRequest $request the original request object
    * @param string $stream the complete HTTP response in case $headers is false, the response body if $headers is an array
    * @param array $headers the http headers received along with the response
    * @param string $statuscode http status code
    * @return void (if parsing of stream fails, set isFault to true plus error code and description)
    *
    * @todo debloat this api: use a struct to holad all of this info? see how ezcmvc does it...
    */
    abstract function decodeStream( $request, $stream, $headers=false, $cookies=array(), $statuscode="200" );

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
        if ( $value instanceof ggWebservicesFault || $value instanceof eZSOAPFault )
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

    function contentType()
    {
        return $this->ContentType;
    }

    function charset()
    {
        return $this->Charset;
    }

    function responseHeaders()
    {
        return array();
    }

    function cookies()
    {
        return $this->Cookies;
    }

    function statusCode()
    {
        return $this->StatusCode;
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

    protected $ContentType = '';
    protected $Charset = 'UTF-8';

    protected $Cookies = array();

    protected $StatusCode = null;
}

?>