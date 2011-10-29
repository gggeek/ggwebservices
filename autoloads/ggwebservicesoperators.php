<?php
/**
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2011
* @license code licensed under the GPL License: see LICENSE file
 **/

class ggWebservicesOperators {

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
            /*case 'get_topics_of_subcat_xml':
            {
                $operatorValue = $this->get_topics_of_subcat_xml();
            } break;*/
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

    private $Operators;
}
?>