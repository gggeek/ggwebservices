<?php
/**
 * Class used to wrap soap responses. Modeled after the eZ Soap equivalent.
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 * @todo add nusoap, native based versions
 */

class ggSOAPResponse extends ggWebservicesResponse
{
/*
    const INVALIDIDERROR = -50;
    const INVALIDRESPONSESTRING = 'Response received from server is not valid jsonrpc';
    const INVALIDIDSTRING = 'Response received from server does not match request id';
*/

    function __construct( $name='', $namespace=null )
    {
        parent::__construct( $name );
        $this->Namespace = $namespace;
    }

    /**
      Returns the json payload for the response.
    */
    function payload( )
    {
        $doc = new DOMDocument( '1.0', 'utf-8' );
        $doc->name = "eZSOAP message";

        $root = $doc->createElementNS( ggSOAPRequest::ENV, ggSOAPRequest::ENV_PREFIX . ':Envelope' );

        $root->setAttribute( 'xmlns:' . ggSOAPRequest::XSI_PREFIX, ggSOAPRequest::SCHEMA_INSTANCE );
        $root->setAttribute( 'xmlns:' . ggSOAPRequest::XSD_PREFIX, ggSOAPRequest::SCHEMA_DATA );
        $root->setAttribute( 'xmlns:' . ggSOAPRequest::ENC_PREFIX, ggSOAPRequest::ENC );

        // add the body
        $body = $doc->createElement(  ggSOAPRequest::ENV_PREFIX . ':Body' );
        $root->appendChild( $body );

        // Check if it's a fault
        if ( $this->Value instanceof ggWebservicesFault )
        {
            $fault = $doc->createElement( ggSOAPRequest::ENV_PREFIX . ':Fault' );

            $faultCodeNode = $doc->createElement( "faultcode", $this->Value->faultCode() );
            $fault->appendChild( $faultCodeNode );

            $faultStringNode = $doc->createElement( "faultstring", $this->Value->faultString() );
            $fault->appendChild( $faultStringNode );

            $body->appendChild( $fault );
        }
        else
        {
            // add the response
            $responseName = $this->Name . "Response";
            $response = $doc->createElement( $responseName );
            $response->prefix = "resp";
            $response->setAttribute( 'xmlns:' . "resp", $this->Namespace );

            $return = $doc->createElement( "return" );
            $return->prefix = "resp";

            $value = ggSOAPRequest::encodeValue( $doc, "return", $this->Value );

            $response->appendChild( $value );

            $body->appendChild( $response );

        }

        $doc->appendChild( $root );

        return $doc->saveXML();
    }

    /**
      Decodes a DOM node and returns the PHP datatype instance of it.
    */
    static function decodeDataTypes( $node, $type="" )
    {
        $returnValue = false;

        $attributeValue = '';
        $attribute = $node->getAttributeNodeNS( ggSOAPRequest::SCHEMA_INSTANCE, 'type' );
        if ( !$attribute )
        {
            $attribute = $node->getAttributeNodeNS( 'http://www.w3.org/1999/XMLSchema-instance', 'type' );
        }
        $attributeValue = $attribute->value;

        $dataType = $type;
        $attrParts = explode( ":", $attributeValue );
        if ( $attrParts[1] )
        {
            $dataType = $attrParts[1];
        }

/*
        $typeNamespacePrefix = $this->DOMDocument->namespaceByAlias( $attrParts[0] );

        check that this is a namespace type definition
                if ( ( $typeNamespacePrefix == ggSOAPRequest::SCHEMA_DATA ) ||
                     ( $typeNamespacePrefix == ggSOAPRequest::ENC )
                     )
TODO: add encoding checks with schema validation.
*/

        switch ( $dataType )
        {
            case "string" :
            case "int" :
            case "float" :
            case 'double' :
            {
                $returnValue = $node->textContent;
            } break;

            case "boolean" :
            {
                if ( $node->textContent == "true" )
                    $returnValue = true;
                else
                    $returnValue = false;
            } break;

            case "base64" :
            {
                $returnValue = base64_decode( $node->textContent );
            } break;

            case "Array" :
            {
                // Get array type
                $arrayType = $node->getAttributeNodeNS( ggSOAPRequest::ENC, 'arrayType' )->value;
                $arrayTypeParts = explode( ":", $arrayType );

                preg_match( "#(.*)\[(.*)\]#",  $arrayTypeParts[1], $matches );

                $type = $matches[1];
                $count = $matches[2];

                $returnValue = array();
                foreach( $node->childNodes as $child )
                {
                    if ( $child instanceof DOMElement )
                    {
                        $returnValue[] = ggSOAPResponse::decodeDataTypes( $child, $type );
                    }
                }
            }break;

            case "SOAPStruct" :
            {
                $returnValue = array();

                foreach( $node->childNodes as $child )
                {
                    if ( $child instanceof DOMElement )
                    {
                        $returnValue[$child->tagName] = ggSOAPResponse::decodeDataTypes( $child );
                    }
                }
            }break;

            default:
            {
                foreach ( $node->childNodes as $childNode )
                {
                    if ( $child instanceof DOMElement )
                    {
                        // check data type for child
                        $attr = $childNode->getAttributeNodeNS( ggSOAPRequest::SCHEMA_INSTANCE, 'type' )->value;

                        $dataType = false;
                        $attrParts = explode( ":", $attr );
                        $dataType = $attrParts[1];

                        $returnValue[$childNode->name()] = ggSOAPResponse::decodeDataTypes( $childNode );
                    }
                }

            } break;
        }

        return $returnValue;
    }

    /**
    * Decodes the JSONRPC response stream.
    * Request is used for matching id.
    * Name is not set to response from request - a bit weird...
    */
    function decodeStream( $request, $stream )
    {
        // save raw data for debugging purposes
        $this->rawResponse = $stream;

        $this->IsFault = 1;
        $this->FaultCode = ggWebservicesResponse::INVALIDRESPONSEERROR;
        $this->FaultString = ggWebservicesResponse::INVALIDREQUESTSTRING;

        $stream = $this->stripHTTPHeader( $stream );
        if ( $stream == '' )
        {
            $this->FaultString .=  " - Could not process XML in response - response is empty!";
            return;
        }

        $dom = new DOMDocument( "1.0" );
        $this->DOMDocument = $dom;

        @$dom->loadXML( $stream );

        if ( !empty( $dom ) )
        {
            // check for fault
            $response = $dom->getElementsByTagNameNS( ggSOAPRequest::ENV, 'Fault' );

            if ( $response->length  == 1 )
            {
                $this->IsFault = 1;
                foreach( $dom->getElementsByTagName( "faultstring" ) as $faultNode )
                {
                    $this->FaultString = $faultNode->textContent;
                    break;
                }

                foreach( $dom->getElementsByTagName( "faultcode" ) as $faultNode )
                {
                    $this->FaultCode = $faultNode->textContent;
                    break;
                }
                return;
            }

            // get the response

            /* Cut from the SOAP spec (1.1):
            The method response is viewed as a single struct containing an accessor
            for the return value and each [out] or [in/out] parameter.
            The first accessor is the return value followed by the parameters
            in the same order as in the method signature.

            Each parameter accessor has a name corresponding to the name
            of the parameter and type corresponding to the type of the parameter.
            The name of the return value accessor is not significant.
            Likewise, the name of the struct is not significant.
            However, a convention is to name it after the method name
            with the string "Response" appended.
            */

            $response = $dom->getElementsByTagNameNS( $request->namespace(), $request->name() . "Response" );

            if ( $response->length == 1 )
            {
                $response = $response->item(0);
                $responseAccessors = $response->childNodes; //$response->getElementsByTagName( 'return' );
                if ( $responseAccessors->length >= 1 )
                {
                    $results = array();
                    foreach( $responseAccessors as $child )
                    {
                        if ( $child instanceof DOMElement )
                        {
                            $results[$child->tagName] = ggSOAPResponse::decodeDataTypes( $child );
                        }

                    }
                    if ( count( $results ) == 1 )
                    {
                        $this->Value = reset( $results );
                    }
                    else
                    {
                        $this->Value = $results;
                    }
                    // single-accessor response: we decode the return value
                    //$returnObject = $responseAccessors->item( 0 );
                    //$this->Value = ggSOAPResponse::decodeDataTypes( $returnObject );
                    $this->IsFault = false;
                    $this->FaultString = false;
                    $this->FaultCode = false;
                }
                else
                {
                    $this->FaultString .=  " - Empty response element found in XML in response";
                }
            }
            else
            {
                //eZDebug::writeError( "Got error from server" );
                $this->FaultString .=  " - Response element not found in XML in response";
            }
        }
        else
        {
            //eZDebug::writeError( "Could not process XML in response" );
            $this->FaultString .=  " - Could not process XML in response";
        }
    }

    public $rawResponse = null;
}

?>
