<?php
/**
 * WSDL helper functions
 *
 * @author G. Giunta
 * @copyright (C) 2011-2012 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 */

/**
* This class should really be renamed to WSDLHelper or something more appropriate
*/
class ggWSDLParser
{
    /**
     * Transforms the array returned by php's soap client getFunctionsResults() method
     * into the format used by xmlrpc's introspection methods (system.listMethods and friends)
     */
    static function transformGetFunctionsResults( $results, $rname, $method = '' )
    {
        switch( $rname )
        {
            case 'system.listMethods':
                foreach ( $results as $key => $value )
                {
                    $results[$key] = preg_replace( array( '/^([^ ]+ )/', '/(\(.+)$/' ), '', $value );
                }
                return $results;
                break;
            case 'system.methodHelp':
                return ''; /// @todo
                break;
            case 'system.methodSignature':
                foreach ( $results as $key => $value )
                {
                    if ( preg_match( '/^([^ ]+) ([^\(]+)\((.+)\)$/', $value, $matches ) )
                    {
                        if ( $method == $matches[2] )
                        {
                            $params = array( $matches[1] );
                            foreach( explode( ', ', $matches[3] ) as $param )
                            {
                                $param = explode( ' ', $param, 2 );
                                $name = substr( $param[1], 1); // php likes dollars in var names
                                $params[$name] = $param[0];
                            }
                            return array( $params );
                        }
                    }
                }
                return array(); /// @todo return error 'method not found'
                break;
        } // switch
    }

    /**
    * Transforms type declarations used eg. in server's registerFunction() calls
    * into xsd type declarations. The starting type decalarations can be either
    * php types or "xml schema" types, in which case they will go trhough unchanged.
    *
    * Simple types (ie. simple types builtin in the xmlschema spec http://www.w3.org/TR/xmlschema-0/#CreatDt)
    * will be prefixed with $xmlschemaprefix, structs/arrays with $soapencprefix
    * (spec at http://schemas.xmlsoap.org/soap/encoding/), complex ones with $targetprefix.*
    *
    * Example returned complex types:
    * . arrayOfstring
    * . choiceOfintOrstring
    * . classmyClass
    *
    * The magic type 'void' will be transformed into ''.
    */
    static function phpType2xsdType( $type, $targetprefix='tnsxsd:', $xmlschemaprefix='xsd:', $soapencprefix='SOAP-ENC:' )
    {
        $type = trim( $type );

        // end user can give us xsd types directly: allow him to
        if ( strpos( $type, $xmlschemaprefix ) === 0 || strpos( $type, $targetprefix ) === 0 || strpos( $type, $soapencprefix ) === 0 )
        {
            return $type;
        }
        else
        {
            switch( strtolower( $type ) )
            {
                case 'string':
                    return "{$xmlschemaprefix}string";
                case 'int':
                case 'integer':
                    return "{$xmlschemaprefix}int";
                case 'float':
                    return "{$xmlschemaprefix}float";
                case 'bool':
                case 'boolean':
                    return "{$xmlschemaprefix}boolean";
                // nb: not a php type
                case 'double':
                    return "{$xmlschemaprefix}double";
                // nb: not a php type
                case 'long':
                    return "{$xmlschemaprefix}long";
                case 'array':
                    return "{$soapencprefix}Array";
                case 'void':
                    return '';
                case 'null':
                    /// @todo
                case 'mixed':
                    return "{$xmlschemaprefix}anyType";
                default:
                    if ( strpos( $type, '|' ) !== false )
                    {
                        // build a complex type representation
                        $subtypes = explode( '|', $type );
                        foreach( $subtypes  as $i => $type )
                        {
                            $subtypes[$i] = /*str_replace( $xmlschemaprefix, '',*/ self::phpType2xsdType( $type, $targetprefix, $xmlschemaprefix, $soapencprefix ) /*)*/;
                        }
                        return $targetprefix . 'choiceOf' . implode( 'Or', $subtypes );
                    }
                    else if ( strpos( $type, 'array of ' ) === 0 )
                    {
                        $subtype = self::phpType2xsdType( substr( $type, 9 ), $targetprefix, $xmlschemaprefix, $soapencprefix );
                        /// @todo what if target also is complex?
                        return $targetprefix . "arrayOf" . /*str_replace( $xmlschemaprefix, '',*/ $subtype /*)*/;
                    }
                    else if ( class_exists( $type ) )
                    {
                        return $targetprefix . "class" . $type;
                    }

                    return "{$xmlschemaprefix}anyType";
                }
        }
    }

}

?>