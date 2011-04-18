<?php
/**
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2011
 **/

class ggWebservicesOperators {

    /**
     Constructor
    */
    function ggWebservicesOperators() {
        $this->Operators = array ( 'washxml', 'washxmlcomment', 'washxmlcdata', 'xsdtype' );
    }

    /**
     Returns the operators in this class.
     @return array
    */
    function operatorList() {
        return $this->Operators;
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
        return array(
            'washxml' => array(),
            'washxmlcomment' => array(),
            'washxmlcdata' => array(),
            'ws_send' => array(
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
                ),
            'xsdtype' => array(
                'xsdprefix' => array(
                    'type' => 'string',
                    'required' => true,
                    'default' => 'xsd:' ),
                'targetprefix' => array(
                    'type' => 'string',
                    'required' => true,
                    'default' => 'tnsxsd:' ),
            )
        );
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
                break;
            case 'xsdtype':
                $operatorValue = ggWSDLParser::phpType2xsdType( $operatorValue, $namedParameters['xsdprefix'], $namedParameters['targetprefix'] );
                break;
        }
    }

    private $Operators;
}
?>