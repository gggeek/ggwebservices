<?php
/**
 * wrapper for eZWebesrvicesResponse, to make it usable in templates
 *
 * @author G. Giunta
 * @copyright (C) 2013-2016 G. Giunta
 */

class ggeZWebservicesResponse
{
    protected $resp = null;
    protected $attrs = array(
        'isFault', 'faultCode', 'faultString', 'value', 'name', 'contentType',
        'charset', 'responseHeaders', 'cookies', 'statusCode'
    );

    /**
     * @param ggWebservicesResponse $response
     */
    function __construct( $response )
    {
        $this->resp = $response;
        switch( get_class( $response ) )
        {
            case "ggRESTResponse":
                $this->attrs[] = 'jsonpCallback';
                break;
            case "ggRESTResponse":
                $this->attrs[] = 'id';
                break;
            case "ggSOAPResponse":
                $this->attrs[] = 'ns';
                break;
        }
    }

    function attributes()
    {
        return $this->attrs;
    }

    function hasattribute( $attr )
    {
        return in_array( $attr, $this->attrs );
    }

    function attribute( $attr )
    {
        switch( $attr )
        {
            case 'isFault':
                return $this->resp->isFault();
            case 'faultCode':
                return $this->resp->faultCode();
            case 'faultString':
                return $this->resp->faultString();
            case 'value':
                $val = $this->resp->value();
                // if we have an object and its not ggSimpleTemplateXML, cast it to something usable by tpl
                return ( is_object( $val ) && get_class( $val ) != 'ggSimpleTemplateXML' ) ? (array) $val : $val;
            case 'name':
                return $this->resp->name();
            case 'contentType':
                return $this->resp->contentType();
            case 'charset':
                return $this->resp->charset();
            case 'responseHeaders':
                return $this->resp->responseHeaders();
            case 'cookies':
                return $this->resp->cookies();
            case 'statusCode':
                return $this->resp->statusCode();
            case 'id':
                return $this->resp->id();
            case 'jsonpCallback':
                return $this->resp->jsonpCallback();
            case 'ns':
                return $this->resp->ns();
        }
    }
}