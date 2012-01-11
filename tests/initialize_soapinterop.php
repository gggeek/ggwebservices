<?php
/**
 * Implements the methods for the passing the interop test suite defined at
 * http://www.xmethods.net/soapbuilders/proposal.html
 *
 * @version $Id$
 * @author G. Giunta
 * @copyright (C) 2011-2012 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @todo implement struct tests
 */

$server->registerFunction(
    'echoString',
    array( 'inputString' => 'string' ),
    'string' );

$server->registerFunction(
    'echoStringArray',
    array( 'inputStringArray' => 'array of string' ),
    'array of string' );

$server->registerFunction(
    'echoInteger',
    array( 'inputInteger' => 'int' ),
    'int' );

$server->registerFunction(
    'echoIntegerArray',
    array( 'inputIntegerArray' => 'array of int' ),
    'array of int' );

$server->registerFunction(
    'echoFloat',
    array( 'inputFloat' => 'float' ),
    'float' );

$server->registerFunction(
    'echoFloatArray',
    array( 'inputFloatarray' => 'array of float' ),
    'array of float' );

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
    array(), // no input parameter
    'void' );

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
    return (string)$inputString;
}

function echoStringArray( $inputStringArray )
{
    foreach( $inputStringArray as $i => $s)
    {
        $inputStringArray[$i] = (string)$s;
    }
    return $inputStringArray;
}

function echoInteger( $inputInteger )
{
    return (integer)$inputInteger;
}

function echoIntegerArray( $inputIntegerArray )
{
    foreach( $inputIntegerArray as $i => $s)
    {
        $inputIntegerArray[$i] = (integer)$s;
    }
    return $inputIntegerArray;
}

function echoFloat( $inputFloat )
{
    return (float)$inputFloat;
}

function echoFloatArray( $inputFloatArray )
{
    foreach( $inputFloatArray as $i => $s)
    {
        $inputFloatArray[$i] = (float)$s;
    }
    return $inputFloatArray;
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