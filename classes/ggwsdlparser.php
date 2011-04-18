<?php
/**
 * WSDL helper functions
 *
 * @author G. Giunta
 * @version
 * @copyright (C) G. Giunta 2011
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

    // transforms type declarations used eg. in server's registerFunction() calls
    // into xsd type declarations
    static function phpType2xsdType( $type, $xmlschemaprefix='xsd:', $targetprefix='tnsxsd:' )
    {
        // end user can give us xsd types directly: allow him to
        if ( strpos( $type, $xmlschemaprefix ) === 0 )
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
                    return "{$xmlschemaprefix}integer";
                case 'float':
                    return "{$xmlschemaprefix}float";
                case 'double':
                    return "{$xmlschemaprefix}double";
                case 'bool':
                case 'boolean':
                    return "{$xmlschemaprefix}boolean";
                case 'array':
                case 'mixed':
                    /// @todo
                    break;
                default:
                    if ( strpos( $type, '|' ) !== false )
                    {
                        // build a complex type representation
                        $subtypes = explode( '|', $type );
                        foreach( $subtypes  as $i => $type )
                        {
                            $subtypes[$i] = str_replace( $xmlschemaprefix, '',  self::phpType2xsdType( $type, $xmlschemaprefix, $targetprefix ) );
                        }
                        return $targetprefix . 'choiceOf' . implode( 'Or' ) . $subtypes;
                    }
                    else if ( strpos( $type, 'array of ' ) === 0 )
                    {
                        $subtype = self::phpType2xsdType( substr( $type, 9 ), $xmlschemaprefix, $targetprefix );
                        /// @todo what if target also is complex?
                        return $targetprefix . "arrayOf" . str_replace( $xmlschemaprefix, '', $subtype );
                    }
                    else if ( class_exists( $type ) )
                    {
                        /// @todo analyze class name and describe it
                        return $targetprefix . "class" . ucfirst( $type );
                    }
                }

            return "{$xmlschemaprefix}anyType";
        }
    }

}

?>