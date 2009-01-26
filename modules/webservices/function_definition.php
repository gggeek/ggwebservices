<?php
/**
 *
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright 2009
 */

$FunctionList = array();
$FunctionList['call'] = array(
    'name'            => 'call',
    //'operation_types' => array( 'read' ),
    'call_method'     => array( //'include_file' => '.../modules/helloworld/helloworldfunctioncollection.php',
                                'class'        => 'ggeZWebservicesClient',
                                'method'       => 'send'
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
                                       'default'  => array() ) ) );

?>