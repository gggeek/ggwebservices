<?php
/**
 * Base class for handling all incoming webservice calls.
 * API based on the eZSOAPServer class from eZP lib dir. See docs in there.
 *
 * Process of handling incoming requests:
 *
 * processRequest
 *   |
 *   -- parseRequest (builds request obj out of received data)
 *   |
 *   -- handleRequest or handleInternalRequest (builds response as plain php objects)
 *   |
 *   -- showResponse (echoes response in correct format, usually via building of response obj)
 *
 * @todo add a better way to register methods, supporting definition of type of return value and per-param help text
 * @todo add support for compressed requests
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

        $namespaceURI = 'unknown_namespace_uri';

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
            if ( $this->isInternalRequest( $functionName ) )
            {
                $response = $this->handleInternalRequest( $functionName, $params );
            }
            else
            {
                $response = $this->handleRequest( $functionName, $params );
            }
            $this->showResponse( $functionName, $namespaceURI, $response );
        }
    }

    /**
    * Verifies if the given request has been registered as an exposed webservice
    * and executes it. Called by processRequest.
    */
    function handleRequest( $functionName, $params )
    {
        if ( array_key_exists( $functionName, $this->FunctionList ) )
        {
            $paramsOk = false;
            foreach( $this->FunctionList[$functionName] as $paramDesc )
            {
                $paramsOk = ( ( $paramDesc === null ) || $this->validateParams( $params, $paramDesc['in'] ) );
                if ( $paramsOk )
                {
                    break;
                }
            }
            if ( $paramsOk )
            {
                if ( strpos($functionName, '::') )
                {
                   return call_user_func_array( explode( '::', $functionName ), $params );
                }
                else
                {
                    return call_user_func_array( $functionName, $params );
                }
            }
            else
            {
                return new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING );
            }
        }
        else
        {
            return new ggWebservicesFault( ggWebservicesResponse::INVALIDMETHODERROR, ggWebservicesResponse::INVALIDMETHODSTRING );
        }
    }

    /**
    * Return true if the webservice method encapsulated by $request is to be handled
    * internally by the server instead of a registered function.
    * Used to handle eg system.* stuff in xmlrpc or json.
    * To be overridden by descendent classes.
    */
    function isInternalRequest( $functionName )
    {
        return false;
    }

    /**
    * Handle execution of server-reserved webservice methods.
    * Returns a php value ( or fault object ).
    * Used to handle eg system.* stuff in xmlrpc or json.
    * Called by processRequest.
    * To be overridden by descendent classes.
    */
    function handleInternalRequest( $functionName, $params )
    {
        // This method should never be called on the base class server, as it has no internal methods.
        // Hence we return an error upon invocation
            return new ggWebservicesFault( ggWebservicesResponse::GENERICRESPONSEERROR, ggWebservicesResponse::GENERICRESPONSESTRING );
    }

    /**
      Registers all functions of an object on the server.
      Returns false if the object could not be registered.
      @todo add optional introspection-based param registering
      @todo add single method registration
      @todo add registration of per-method descriptions
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
                    $this->FunctionList[$objectName."::".$method] = array( 'in' => null, 'out' => 'mixed' );
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
      Multiple signatures can be registered for a given php function (but only one help text)
      @todo add optional introspection-based param registering
    */
    function registerFunction( $name, $params=null, $result='mixed', $description='' )
    {
        if ( $this->isInternalRequest( $name ) )
        {
            return false;
        }
        if ( function_exists( $name ) )
        {
            $this->FunctionList[$name][] = array( 'in' => $params, 'out' => $result );

            if ( $description !== '' || !array_key_exists( $name, $this->FunctionDescription ))
            {
                $this->FunctionDescription[$name] = $description;
            }
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

    /// Contains a list over registered functions, and their dscriptions
    protected $FunctionList = array();
    protected $FunctionDescription = array();
    /// Contains the RAW HTTP post data information
    public $RawPostData;
}

?>
