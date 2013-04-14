<?php
/**
 *
 *
 * @author G. Giunta
 * @copyright (C) 2009-2013 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 */

$FunctionList = array();
$FunctionList['call'] = array(
    'name'            => 'call',
    //'operation_types' => array( 'read' ),
    'call_method'     => array( //'include_file' => '.../modules/helloworld/helloworldfunctioncollection.php',
                                'class'        => 'ggeZWebservicesClient',
                                'method'       => 'call'
                         ),
    //'parameter_type'  => 'standard',
    'parameters'      => array( array( 'name'     => 'server',
                                       'type'     => 'string',
                                       'required' => true ),
                                array( 'name'     => 'method',
                                       'type'     => 'string',
                                       'required' => true ),
                                array( 'name'     => 'parameters',
                                       'type'     => 'array',
                                       'required' => false,
                                       'default'  => array() ),
                                array( 'name'     => 'options',
                                       'type'     => 'array',
                                       'required' => false,
                                       'default'  => array() ) ) );

?>