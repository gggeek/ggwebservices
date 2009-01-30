<?php
/**
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 */

class ggXMLRPCServer extends ggWebservicesServer
{

    /**
    * Echoes the response, setting http headers and such
    */
    function showResponse( $functionName, $namespaceURI, &$value )
    {

        $response = new ggXMLRPCResponse( $functionName );
        $response->setValue( $value );
        $payload = $response->payload();

        //header( "SOAPServer: eZ soap" );
        header( "Content-Type: text/xml; charset=\"UTF-8\"" );
        header( "Content-Length: " . strlen( $payload ) );

        if ( ob_get_length() )
            ob_end_clean();

        print( $payload );
    }

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

    function handleInternalRequest( $functionName, $params )
    {

        switch( $functionName )
        {
            case 'system.listMethods':
                if ( count( $params ) != 0 )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING );
                }
                return array_merge( array_keys( $this->FunctionList ), $this->internalMethods );

            case 'system.methodSignature':
                if ( count( $params ) != 1 || !is_string( $params[0] ) )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING );
                }
                if ( !array_key_exists( $params[0], $this->FunctionDescription ) )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDINTROSPECTIONERROR, ggWebservicesResponse::INVALIDINTROSPECTIONSTRING );
                }
                if ( in_array( $params[0], $this->internalMethods ) )
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
                if ( !array_key_exists( $params[0], $this->FunctionList ) )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDINTROSPECTIONERROR, ggWebservicesResponse::INVALIDINTROSPECTIONSTRING );
                }
                $results = array();
                foreach( $this->FunctionList[$params[0]] as $syntax )
                {
                    $results[] = array_unshift( $syntax, 'mixed' );
                }
                return $results;

            case 'system.methodHelp':
                if ( count( $params ) != 1 || !is_string( $params[0] ) )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING );
                }
                if ( in_array( $params[0], $this->internalMethods ) )
                {
                    switch( $params[0] )
                    {
                        case 'system.listMethods':
                            return 'a';
                        case 'system.methodSignature':
                            return 'b';
                        case 'system.methodHelp':
                            return 'c';
                        case 'system.multicall':
                            return 'd';
                    }
                }
                if ( !array_key_exists( $params[0], $this->FunctionDescription ) )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDINTROSPECTIONERROR, ggWebservicesResponse::INVALIDINTROSPECTIONSTRING );
                }
                return $this->FunctionDescription[$params[0]];

            case 'system.multicall':
                // validate first the multicall syntax
                if ( count( $params ) != 1 || !is_array( $params[0] ) )
                {
                    return new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING );
                }
                foreach( $params[0] as $request )
                {
                    if ( !is_array( $request ) || //count( $request ) != 2 ||
                         !array_key_exists( 'methodName', $request) || !is_string( $request['methodName'] )||
                         !array_key_exists( 'params', $request ) || !is_array( $request['params'] ) )
                    {
                        return new ggWebservicesFault( ggWebservicesResponse::INVALIDPARAMSERROR, ggWebservicesResponse::INVALIDPARAMSSTRING );
                    }
                }
                // then execute all methods
                $results = array();
                foreach( $params[0] as $request)
                {
                    $resp = $this->handleRequest( $request['methodName'], $request['params'] );
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

    var $internalMethods = array(
        //'system.getCapabilities',
        'system.listMethods',
        'system.methodSignature',
        'system.methodHelp',
        'system.multicall' );
}

?>