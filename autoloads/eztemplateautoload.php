<?php

// Operator autoloading

$eZTemplateOperatorArray = array();

$eZTemplateOperatorArray[] =
    array
    (
        'script' => 'extension/ggwebservices/autoloads/ggwebservicesoperators.php',
        'class' => 'ggWebservicesOperators',
        'operator_names' => array( 'washxml', 'washxmlcomment', 'washxmlcdata' )
    );

?>