<?php
/**
 * To allow this server to work in the context of the 'execute' view, we currently
 * limit it to soap 1.1
 *
 * @author G. Giunta
 * @copyright (C) 2011-2020 G. Giunta
 *
 * @todo finish support for soap 1.2
 */

class ggPhpSOAPServer extends ggWebservicesServer
{

    /**
    * We should reimplement this function, and test:
    * if value is an error, using a server to generate an error gives better results?
    */
    /*function showResponse( $functionName, $namespaceURI, &$value )
    {
        if ( $value instanceof ggWebservicesFault || $value instanceof eZSOAPFault )
        {
            $server = new SoapServer( null, array( 'soap_version' => 1, 'uri' => '...' ) );
            $server->fault( $value->faultCode(), $value->faultString() );
       }
       else
       {
           parent::showResponse( $functionName, $namespaceURI, $value );
       }
    }*/

    /**
    * This is a simplified parser: it does not extract the parameters from
    * the incoming payload, but limits itself to injecting the complete
    * payload in the request object
    *
    * @todo parse soap 1.2 requests to extract method name
    */
    function parseRequest( $payload )
    {
        if ( isset( $_SERVER['HTTP_SOAPACTION'] ) )
        {
            // this list of replacements built by testing different clients...
            // we should really parse the xml instead!
            $soapaction = trim( $_SERVER['HTTP_SOAPACTION'], '"' );
            $soapaction = preg_replace( array( '#^urn:#', '#^/*#', '#Action$#' ), '', $soapaction );
            $request = new ggPhpSOAPRequest( $soapaction );
            // this call is used to store the raw  xml in the request object
            $request->decodeStream( $payload );
            $request->setSoapVersion( 1 );
            return $request;
        }
        else
        {
            /*$request = new ggPhpSOAPRequest( '...' );
            $request->decodeStream( $payload );
            $request->setSoapVersion( 2 );
            return $request;*/
            return false;
        }
    }

    /**
    * Reimplemented, because we do not recover params in the request obj
    */
    function processRequest()
    {
        $namespaceURI = 'unknown_namespace_uri';

        /// @todo dechunk, correct encoding, check for supported
        /// http features of the client, etc...
        $data = $this->RawPostData;

        $data = $this->inflateRequest( $data );
        if ( $data === false )
        {
            $this->showResponse(
                'unknown_function_name',
                $namespaceURI,
                new ggWebservicesFault( self::INVALIDCOMPRESSIONERROR, self::INVALIDCOMPRESSIONSTRING ) );
        }

        $request = $this->parseRequest( $data );

        if ( !is_object( $request ) ) /// @todo use is_a instead
        {
            $this->showResponse(
                'unknown_function_name',
                $namespaceURI,
                new ggWebservicesFault( self::INVALIDREQUESTERROR, self::INVALIDREQUESTSTRING ) );
        }
        else
        {
            $this->processRequestObj( $request );
        }
    }

    /**
    * Shall we reimplement handlerequest?
    * Ideas:
    * . let it handle requests only to functions that have no user wsdl registered
    * . always error out, since we favor going through processRequest
    */
    /*function handleRequest( $functionName, $params )
    {
    }*/

    /**
    * Substitute for handleRquest() + echo results
    */
    function processRequestObj( $request )
    {
        $functionName = $request->name();
        if ( array_key_exists( $functionName, $this->FunctionList ) )
        {
            /// we use a self-generated wsdl (even if user gave no param description), as it is supposedly better than nothing...
            $wsdlUrl = ggeZWebservices::methodsWSDL( $this, array( $functionName ), $functionName, true );
            $server = new SoapServer( $wsdlUrl, array( 'soap_version' => $request->getSoapVersion(), /*'uri' => '...'*/ ) );
            foreach( $this->FunctionList as $function => $desc )
            {
                $server->addFunction( $function );
            }
            //ob_start();
            $server->handle( $request->payload() );
            //$response = ob_get_clean();
        }
        else
        {
            $this->showResponse( $functionName, '...', new ggWebservicesFault( self::INVALIDMETHODERROR, self::INVALIDMETHODSTRING . " '$functionName'" ) );
        }
    }

    /**
     * Alter slightly from parent class to allow usage of custom wsdl files:
     * pass wsdl as 2nd param instead of an array, and wsdl version as 3rd param (defaulting to 1)
     */
    function registerFunction( $name, $params=null, $result='mixed', $description='' )
    {
        if ( is_string( $params ) )
        {
            if ( $return = parent::registerFunction( $name, null, 'mixed', $description ) )
            {
                $version = ( $result == 2 ) ? 2 : 1;
                $this->wsdl[$name][$version] = $params;
            }
        }
        else
        {
            $return = parent::registerFunction( $name, $params, $result, $description );
        }
        return $return;
    }

    /**
    * Returns the user-registered wsdl file corresponding to one of the registered services, or null
    * NB: if user did register a php function without providing a wsdl by itself, this will return null
    * @param string $function the name of a ws
    * @todo we should be able to generate the wsdl for methods where user did not provide any,
    *       and make this protected
    * @see ggezwebservices::generateMethodsWSDL
    */
    function userWsdl( $function, $version=1 )
    {
        if ( isset( $this->wsdl[$function][$version] ) )
        {
            return $this->wsdl[$function][$version];
        }
        return null;
    }

    /*function methodWsdl( $function, $version=1 )
    {
        $wsdl = $this->userWsdl( $function, $version );
        if ( $wsdl == null )
        {
            /// @todo generate wsdl (see ggezwebservices::generateMethodsWSDL)
        }
    }*/

    protected $wsdl = array();
    /// we do not use the ggPhpSOAPResponse class, but ggSOAPResponse, because the latter
    /// is able to generate serialization of error messages. And this server should only
    /// manage response objects in error conditions
    protected $ResponseClass = 'ggSOAPResponse';
    //protected $SoapVersion = SOAP_1_1;
}
