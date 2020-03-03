<?php
/**
 * @author G. Giunta
 * @copyright (C) 2009-2020 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 **/

class ggWebservicesOperators
{
     static $operators = array(
         'washxml' => array(),
         'washxmlcomment' => array(),
         'washxmlcdata' => array(),
         /*'ws_send' => array(
             'server' => array(
                 'type' => 'string',
                 'required' => true ),
             'method' => array(
                 'type' => 'string',
                 'required' => true ),
             'params' => array(
                 'type' => 'array',
                 'required' => true,
                 'default' => array() )
             ),*/
         'xsdtype' => array(
             'targetprefix' => array(
                'type' => 'string',
                'required' => false,
                'default' => 'tnsxsd:' ),
             'xsdprefix' => array(
                 'type' => 'string',
                 'required' => false,
                 'default' => 'xsd:' ),
             'soapencprefix' => array(
                 'type' => 'string',
                 'required' => false,
                 'default' => 'SOAP-ENC:' ),
             ),
         'classInspect' => array()
     );

    /**
     Returns the operators in this class.
     @return array
    */
    function operatorList()
    {
        return array_keys( self::$operators );
    }

    /**
     @return true to tell the template engine that the parameter list
     exists per operator type; this is needed for operator classes
     that have multiple operators.
    */
    function namedParameterPerOperator() {
        return true;
    }

    /**
     @see eZTemplateOperator::namedParameterList()
     @return array
    */
    function namedParameterList() {
        return self::$operators;
    }

    /**
     Executes the needed operator(s).
     Checks operator names, and calls the appropriate functions.
    */
    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters ) {
        switch ($operatorName)
        {
            case 'washxml':
                $operatorValue = str_replace( array( '&', '"', "'", '<', '>' ), array( '&amp;', '&quot;', '&apos;', '&lt;', '&gt;' ), $operatorValue );
                break;
            case 'washxmlcomment':
                // in xml comments the -- string is not permitted
                $operatorValue = str_replace( '--', '_-', $operatorValue );
                break;
            case 'washxmlcdata':
                /// @todo
                eZDebug::writeWarning( 'Template operator washxmlcdata not yet implemented, it should not be used!', __METHOD__ );
                break;
            case 'xsdtype':
                $operatorValue = ggWSDLParser::phpType2xsdType( $operatorValue, $namedParameters['targetprefix'], $namedParameters['xsdprefix'], $namedParameters['soapencprefix'] );
                break;
            case 'classInspect':
                $operatorValue = ggeZWebservices::classInspect( $operatorValue );
                break;
        }
    }

}
