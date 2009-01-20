<?php
/**
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2008
 **/

class ggWebServicesOperators {

    /**
     Constructor
    */
    function ggWebServicesOperators() {
        $this->Operators = array ( 'washxml', 'washxmlcomment', 'washxmlcdata', 'ws_send' );
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
            {
                $operatorValue = str_replace( array( '&', '"', "'", '<', '>' ), array( '&amp;', '&quot;', '&apos;', '&lt;', '&gt;' ), $operatorValue );
            } break;
            case 'washxmlcomment':
            {
                // in xml comments the -- string is not permitted
                $operatorValue = str_replace( '--', '_-', $operatorValue );
            } break;
            case 'washxmlcdata':
            {
                /// @todo
            } break;
            case 'ws_send':
            {
                $result = ggeZWebServicesClient::send(
                    $namedParameters['server'],
                    $namedParameters['method'],
                    $namedParameters['params'] );
                /// @todo verify what happens if $result is a simplexml object...
                $operatorValue = $result;
            } break;
        }
    }

    private var $Operators;
}
?>