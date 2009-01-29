<?php
/**
 * Base class for handling all incoming webservice calls.
 * API based on the eZSOAPServer class from eZP lib dir. See docs in there.
 */

abstract class ggWebservicesServer
{

    /**
    * Creates a new server object
    * If raw_data is not passed to it, it initializes self from POST data
    */
    function __construct( $raw_data=null )
    {
        if ( $raw_data === null )
        {
            $this->RawPostData = file_get_contents('php://input');
        }
        else
        {
            $this->RawPostData = $raw_data;
        }
    }

    /**
    * Echoes the response, setting http headers and such
    */
    abstract function showResponse( $functionName, $namespaceURI, &$value );

    /**
    * Takes as input the request payload and returns a request obj or false
    */
    abstract function parseRequest( $payload );

    /**
      Processes the request and prints out the proper response.
    */
    function processRequest()
    {
        /* tis' the job of the index page, not of the class!
        global $HTTP_SERVER_VARS;
        if ( $HTTP_SERVER_VARS["REQUEST_METHOD"] != "POST" )
        {
            print( "Error: this web page does only understand POST methods" );
            exit();
        }
        */

        $namespaceURI = 'nknown_namespace_uri';

        /// @todo reinflate, dechunk, correct encoding, check for supported
        /// http features of the client, etc...
        $data = $this->RawPostData;

        $request = $this->parseRequest( $data );

        if ( !is_object( $request ) ) /// @todo use is_a instead
        {
            $this->showResponse(
                'unknown_function_name',
                $namespaceURI,
                new ggWebservicesFault( ggWebservicesResponse::INVALIDREQUESTERROR, ggWebservicesResponse::INVALIDREQUESTSTRING ) );
        }
        else
        {
            $functionName = $request->name();
            $params = $request->parameters();

            /// @todo add support for system.multiCall, system.methodList and system.methodHelp

            if ( array_key_exists( $functionName, $this->FunctionList ) )
            {
                $paramsOk = false;
                foreach( $this->FunctionList[$functionName] as $paramDesc )
                {
                    $paramsOk = ( ( $paramDesc === null ) || $this->validateParams( $params, $paramDesc ) );
                    if ( $paramsOk )
                    {
                        break;
                    }
                }
                if ( $paramsOk )
                {
                    if ( strpos($functionName, '::') )
                    {
                       $resp = call_user_func_array( explode( '::', $functionName ), $params );
                    }
                    else
                    {
                        $resp = call_user_func_array( $functionName, $params );
                    }
                    $this->showResponse( $functionName, $namespaceURI, $resp );
                }
                else
                {
                    $this->showResponse(
                        $functionName,
                        $namespaceURI,
                        new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING ) );
                }
            }
            else
            {
                $this->showResponse(
                    $functionName,
                    $namespaceURI,
                    new ggWebservicesFault( ggWebservicesResponse::INVALIDMETHODERROR, ggWebservicesResponse::INVALIDMETHODSTRING ) );
            }
        }
    }

    /**
      Registers all functions of an object on the server.
      Returns false if the object could not be registered.
      @todo add optional introspection-based param registering
      @todo add single method registration
    */
    function registerObject( $objectName, $includeFile = null )
    {
        // file_exists check is useless, since it does not scan include path. Let coder eat his own dog food...
        if ( $includeFile !== null ) //&& file_exists( $includeFile ) )
            include_once( $includeFile );

        if ( class_exists( $objectName ) )
        {
            $methods = get_class_methods( $objectName );
            foreach ( $methods as $method )
            {
                /// @todo check also for magic methods not to be registered!
                if ( strcasecmp ( $objectName, $method ) )
                    $this->FunctionList[$objectName."::".$method] = array( null );
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
      Registers a new function on the server.
      Returns false if the function could not be registered.
      If params is an array of name => type strings, params will be checked for consistency.
      Multiple signatures can be registered for a given php function
      @todo add optional introspection-based param registering
    */
    function registerFunction( $name, $params=null )
    {
        if ( function_exists( $name ) )
        {
            $this->FunctionList[$name][] = $params;
            return true;
        }
        else
        {
            return false;
        }
    }

    /// @todo check type and names of params
    function validateParams( $params, $paramDesc )
    {
        return true;
    }

    /// Contains a list over registered functions
    protected $FunctionList = array();
    /// Contains the RAW HTTP post data information
    public $RawPostData;
}

?>
