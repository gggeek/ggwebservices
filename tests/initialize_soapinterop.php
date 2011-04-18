<?php
/**
 * Implements the methods for the passing the interop test suite defined at
 * http://www.xmethods.net/soapbuilders/proposal.html
 *
 * @version $Id$
 * @author
 * @license
 * @copyright G. Giunta 2011
 */

$server->registerFunction(
    'echoString',
    array( 'inputString' => 'string' ),
    'string' );

$server->registerFunction(
    'echoStringArray',
    array( 'inputStringArray' => 'array of string' ),
    '' );

$server->registerFunction(
    'echoInteger',
    array( 'inputInteger' => 'int' ),
    'int' );

$server->registerFunction(
    'echoIntegerArray',
    array( 'inputIntegerArray' => 'array of int' ),
    '' );

$server->registerFunction(
    'echoFloat',
    array( 'inputFloat' => 'float' ),
    'float' );

$server->registerFunction(
    'echoFloatArray',
    array( 'inputFloatarray' => 'array of float' ),
    '' );

$server->registerFunction(
    'echoStruct',
    array(),
    '' );

$server->registerFunction(
    'echoStructArray',
    array(),
    '' );

$server->registerFunction(
    'echoVoid',
    array(),
    '' );

$server->registerFunction(
    'echoBase64',
    array( 'InputBase64' => 'xsd:base64Binary' ),
    'xsd:base64Binary' );

$server->registerFunction(
    'echoDate',
    array( 'inputDate' => 'xsd:dateTime' ),
    'xsd:dateTime' );

function echoString( $inputString )
{
    return $inputString;
}

function echoStringArray( $inputStringArray )
{
}

function echoInteger( $inputInteger )
{
    return $inputInteger;
}

function echoIntegerArray( $inputIntegerArray )
{
}

function echoFloat( $inputFloat )
{
    return $inputFloat;
}

function echoFloatArray( $inputFloatArray )
{
}

function echoStruct( $inputStruct )
{
}

function echoStructArray( $inputStructArray )
{
}

function echoVoid()
{
}

function echoBase64( $inputBase64 )
{
    return $inputBase64;
}

function echoDate( $inputDate )
{
    return $inputDate;
}

?>