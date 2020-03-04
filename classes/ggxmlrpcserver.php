<?php
/**
 *
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 *
 * @see http://www.xmlrpc.com/spec
 */

class ggXMLRPCServer extends ggWebservicesServer
{
    /**
      Processes the request and returns a request object (or false).
    */
    function parseRequest( $payload )
    {
        $request = new ggXMLRPCRequest();
        if ( $request->decodeStream( $payload ) )
        {
            return $request;
        }
        else
        {
            return false;
        }
    }

    function isInternalRequest( $functionName )
    {
        return in_array( $functionName, $this->internalMethods );
    }

    /**
     * Returns the list of available webservices. Includes the internal ones
     * @return array
     */
    public function registeredMethods()
    {
        return array_merge( array_keys( $this->FunctionList ), $this->internalMethods );
    }

    /**
    * 3rd param is a hack used to allow a jsonrpc server to take advantage of this method
    */
    function handleInternalRequest( $functionName, $params, $server=null )
    {
        if ( $server === null )
        {
            $server = $this;
        }
        switch( $functionName )
        {
            case 'system.listMethods':
                if ( count( $params ) != 0 )
                {
                    return new ggWebservicesFault( self::INVALIDPARAMSERROR, self::INVALIDPARAMSSTRING );
                }
                return $server->registeredMethods();

            case 'system.methodSignature':
                if ( count( $params ) != 1 || !is_string( $params[0] ) )
                {
                    return new ggWebservicesFault( self::INVALIDPARAMSERROR, self::INVALIDPARAMSSTRING );
                }
                if ( in_array( $params[0], $server->internalMethods ) )
                {
                    switch( $params[0] )
                    {
                        case 'system.listMethods':
                            return array( array( 'array' ) );
                        case 'system.methodSignature':
                            return array( array( 'array', 'string' ) );
                        case 'system.methodHelp':
                            return array( array( 'string', 'string' ) );
                        case 'system.multicall':
                            return array( array( 'array', 'array' ) );
                    }
                }
                if ( !array_key_exists( $params[0], $server->FunctionList ) )
                {
                    return new ggWebservicesFault( self::INVALIDINTROSPECTIONERROR, self::INVALIDINTROSPECTIONSTRING );
                }
                $results = array();
                foreach( $server->FunctionList[$params[0]] as $syntax )
                {
                    if ( $syntax['in'] !== null )
                    {
                        $syntax['in'] = array_values( $syntax['in'] );
                        array_unshift( $syntax['in'], $syntax['out'] );
                        $results[] = $syntax['in'];
                    }
                }
                return $results;

            case 'system.methodHelp':
                if ( count( $params ) != 1 || !is_string( $params[0] ) )
                {
                    return new ggWebservicesFault( self::INVALIDPARAMSERROR, self::INVALIDPARAMSSTRING );
                }
                if ( in_array( $params[0], $server->internalMethods ) )
                {
                    switch( $params[0] )
                    {
                        case 'system.listMethods':
                            return 'This method lists all the methods that the server knows how to dispatch';
                        case 'system.methodSignature':
                            return 'Returns an array of known signatures (an array of arrays) for the method name passed. First member of each array is the return type';
                        case 'system.methodHelp':
                            return 'Returns help text if defined for the method passed, otherwise returns an empty string';
                        case 'system.multicall':
                            return 'Boxcar multiple RPC calls in one request. See http://www.xmlrpc.com/discuss/msgReader$1208 for details';
                    }
                }
                if ( !array_key_exists( $params[0], $server->FunctionDescription ) )
                {
                    return new ggWebservicesFault( self::INVALIDINTROSPECTIONERROR, self::INVALIDINTROSPECTIONSTRING );
                }
                return $server->FunctionDescription[$params[0]];

            case 'system.multicall':
                // validate first the multicall syntax
                if ( count( $params ) != 1 || !is_array( $params[0] ) )
                {
                    return new ggWebservicesFault( self::INVALIDPARAMSERROR, self::INVALIDPARAMSSTRING );
                }
                foreach( $params[0] as $request )
                {
                    if ( !is_array( $request ) || //count( $request ) != 2 ||
                         !array_key_exists( 'methodName', $request) || !is_string( $request['methodName'] )||
                         !array_key_exists( 'params', $request ) || !is_array( $request['params'] ) )
                    {
                        return new ggWebservicesFault( self::INVALIDPARAMSERROR, self::INVALIDPARAMSSTRING );
                    }
                }
                // then execute all methods
                $results = array();
                foreach( $params[0] as $request)
                {
                    $resp = $server->handleRequest( $request['methodName'], $request['params'] );
                    if ( is_a( $resp, 'ggWebservicesFault' ) )
                    {
                        $results[] = array( 'faultCode' => $resp->faultCode(), 'faultString' => $resp->faultString() );
                    }
                    else
                    {
                        $results[] = $resp;
                    }
                }
                return $results;
            default:
                return parent::handleInternalRequest( $functionName, $params );
        }
    }

    /**
    * @todo do more validation on base64/datetime values (right now they could be switched and still validated)
    * @todo do more validation of array vs. struct values
    */
    function validateParams( $params, $paramDesc )
    {
        // methods registered without a sig are always accepted
        if ( $paramDesc === null )
        {
            return true;
        }
        if ( count( $params ) != count( $paramDesc ) )
        {
            return false;
        }
        // in xmlrpc, params are positional, so discard param name if given in its syopsis
        $paramDesc = array_values( $paramDesc );
        foreach( array_values( $params ) as $key => $param )
        {
            $paramtype = gettype( $param );
            if ( !array_key_exists( $paramtype, $this->typeMap ) )
            {
                // catches 'NULL', 'resource' php types, which are never returned
                // by php_xmlrpc_decode anyway... add an assert here ???
                return false;
            }
            if ( $paramDesc[$key] == 'mixed' )
            {
                continue;
            }
            if ( !in_array( $paramDesc[$key], $this->typeMap[$paramtype] ) )
            {
    				return false;
            }
        }
		return true;

    }

    /// would be nice to have declared as static; this way a server obj (or subclass) is allowed to manipulate it
    var $internalMethods = array(
        //'system.getCapabilities',
        'system.listMethods',
        'system.methodSignature',
        'system.methodHelp',
        'system.multicall' );

    /// map of valid types for param validation (php type to string used in registration)
    /// would be nice to have declared as static, but we need late static binding to allow jsonrpc subclass to change it
    var $typeMap = array(
        'string'  => array( 'string' ),
        'integer' => array( 'i4', 'int', 'integer', 'number' ),
        'double'  => array( 'double', 'float', 'number' ),
        'boolean' => array( 'bool', 'boolean' ),
        'array'   => array( 'array', 'struct' ),
        'object'  => array( 'base64', 'dateTime.iso8601' ),
    );

    protected $ResponseClass = 'ggXMLRPCResponse';
}
