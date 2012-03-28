<?php
/**
 * Class used to wrap soap requests. Code copy'n'pasted from eZ SOAP class
 *
 * @author G. Giunta
 * @copyright (C) 2009-2012 G. Giunta
 *
 * @todo (!important) add a nusoap version
 * @todo using SOAP 1.2 requests can be serialized using GET too...
 */

class ggSOAPRequest extends ggWebservicesRequest
{
    function __construct( $name='', $parameters=array(), $namespace=null )
    {
        parent::__construct( $name, $parameters );
        $this->ns = $namespace;
    }

    /// @todo implement decodeStream !!!
    function decodeStream( $rawResponse )
    {
        return false;
    }

    function payload()
    {
        /*return json_encode( array( 'method' => $this->Name, 'params' => $this->Parameters, 'id' => $this->Id ) );*/

        // eZ SOAP version
        $doc = new DOMDocument( "1.0" );
        $doc->name = 'eZSOAP message';

        $root = $doc->createElementNS( ggSOAPRequest::ENV, ggSOAPRequest::ENV_PREFIX . ':Envelope' );

        $root->setAttribute( ggSOAPRequest::ENV_PREFIX . ':encodingStyle', ggSOAPRequest::ENC );
        $root->setAttribute( 'xmlns:' . ggSOAPRequest::XSI_PREFIX, ggSOAPRequest::SCHEMA_INSTANCE );
        $root->setAttribute( 'xmlns:' . ggSOAPRequest::XSD_PREFIX, ggSOAPRequest::SCHEMA_DATA );
        $root->setAttribute( 'xmlns:' . ggSOAPRequest::ENC_PREFIX, ggSOAPRequest::ENC );

        // add the body
        $body = $doc->createElement( ggSOAPRequest::ENV_PREFIX . ':Body' );

        $body->setAttribute( 'xmlns:' . ggSOAPRequest::REQ_PREFIX, $this->ns );

        /*foreach( $this->BodyAttributes as $name => $value )
        {
            $body->setAttribute( $name, $value );
        }*/

        $root->appendChild( $body );

        // add the request
        $request = $doc->createElement( ggSOAPRequest::REQ_PREFIX . ':' . $this->Name );

        // add the request parameters
        /// @todo add support for pre-encoded params...
        foreach ( $this->Parameters as $key => $parameter )
        {
            $param = ggSOAPRequest::encodeValue( $doc, $key, $parameter );

            //if ( $param == false )
            //    eZDebug::writeError( "Error enconding data for payload", "eZSOAPRequest::payload()" );
            $request->appendChild( $param );
        }

        $body->appendChild( $request );
        $doc->appendChild( $root );

        return $doc->saveXML();
    }

    /**
      Encodes a PHP variable into a SOAP datatype.
      @todo move this logic into ggWSDLParser class
    */
    static function encodeValue( $doc, $name, $value )
    {
        switch ( gettype( $value ) )
        {
            case "string" :
            {
                $node = $doc->createElement( $name, $value );
                $node->setAttribute( ggSOAPRequest::XSI_PREFIX . ':type',
                                     ggSOAPRequest::XSD_PREFIX . ':string' );
                return $node;
            } break;

            case "boolean" :
            {
                $node = $doc->createElement( $name, $value ? 'true' : 'false' );
                $node->setAttribute( ggSOAPRequest::XSI_PREFIX . ':type',
                                     ggSOAPRequest::XSD_PREFIX . ':boolean' );
                return $node;
            } break;

            case "integer" :
            {
                $node = $doc->createElement( $name, $value );
                $node->setAttribute( ggSOAPRequest::XSI_PREFIX . ':type',
                                     ggSOAPRequest::XSD_PREFIX . ':int' );
                return $node;
            } break;

            case "double" :
            {
                $node = $doc->createElement( $name, $value );
                $node->setAttribute( ggSOAPRequest::XSI_PREFIX . ':type',
                                     ggSOAPRequest::XSD_PREFIX . ':float' );
                return $node;
            } break;

            case "array" :
            {
                $arrayCount = count( $value );

                $isStruct = false;
                // Check for struct
                $i = 0;
                foreach( $value as $key => $val )
                {
                    if ( $i !== $key )
                    {
                        $isStruct = true;
                        break;
                    }
                    $i++;
                }

                if ( $isStruct == true )
                {
                    $node = $doc->createElement( $name );
                    $node->setAttribute( ggSOAPRequest::XSI_PREFIX . ':type',
                                         ggSOAPRequest::ENC_PREFIX . ':SOAPStruct' );

                    foreach( $value as $key => $val )
                    {
                        $subNode = ggSOAPRequest::encodeValue( $doc, (string)$key, $val );
                        $node->appendChild( $subNode );
                    }
                    return $node;
                }
                else
                {
                    $node = $doc->createElement( $name );
                    $node->setAttribute( ggSOAPRequest::XSI_PREFIX . ':type',
                                         ggSOAPRequest::ENC_PREFIX . ':Array' );
                    $node->setAttribute( ggSOAPRequest::ENC_PREFIX . ':arrayType',
                                         ggSOAPRequest::XSD_PREFIX . ":string[$arrayCount]" );

                    foreach ( $value as $arrayItem )
                    {
                        $subNode = ggSOAPRequest::encodeValue( $doc, "item", $arrayItem );
                        $node->appendChild( $subNode );
                    }

                    return  $node;
                }
            } break;
        }

        return false;
    }

    /**
      Returns the request target namespace.
    */
    public function ns()
    {
        return $this->ns;
    }

    public function setNamespace( $ns )
    {
        $this->ns = $ns;
    }

    public function setSoapVersion( $version )
    {
        $this->SoapVersion = $version;
        if ( $this->SoapVersion == 2 )
        {
            /// @todo add also "action =..." ?
            $this->ContentType = 'application/soap+xml';
        }
        else
        {
            $this->ContentType = 'text/xml';
        }
    }

    public function getSoapVersion()
    {
        return $this->SoapVersion;
    }

    public function ContentType()
    {
        if ( $this->SoapVersion == 2 )
        {
            /// @todo add also "action =..." ?
            return 'application/soap+xml';
        }
        else
        {
            return 'text/xml';
        }
    }

    public function requestHeaders()
    {
        if ( $this->SoapVersion == 2 )
        {
            return array( 'Accept' => 'application/soap+xml' );
        }
        else
        {
            return array( 'SOAPAction' => $this->ns() . '/' . $this->name() );
        }
    }

    protected $ns;

    const ENV = "http://schemas.xmlsoap.org/soap/envelope/";
    const ENC = "http://schemas.xmlsoap.org/soap/encoding/";
    const SCHEMA_INSTANCE = "http://www.w3.org/2001/XMLSchema-instance";
    const SCHEMA_DATA = "http://www.w3.org/2001/XMLSchema";

    const ENV_PREFIX = "SOAP-ENV";
    const ENC_PREFIX = "SOAP-ENC";
    const XSI_PREFIX = "xsi";
    const XSD_PREFIX = "xsd";
    const REQ_PREFIX = "tns"; // was: req

    protected $Verb = 'POST';
    /// 1 for SOAP_1_1, 2 for SOAP_1_2
    protected $SoapVersion = 1;

}

?>