<?php
/**
 * Implementation of the webservices for eZDebug: immplement the system.* ones
 * that are standfard for xmlrpc
 *
 * @version $Id$
 * @author G. Giunta
 * @copyright (C) G. Giunta 2010
 * @license code licensed under the GNU GPL 2.0: see README
 *
 */

class ggwebservicesJSCFunctions
{

    static function listMethods( )
    {
        $methods = array();
        $ini = eZINI::Instance( 'ezjscore.ini' );
        foreach( $ini->groups() as $blockname => $data )
        {
            if ( strpos( $blockname, 'ezjscServer_' ) === 0 )
            {
                $classname = substr( $blockname, 12 );

                // replicate same logic as ezjscore
                if ( $ini->hasVariable( $blockname, 'TemplateFunction' ) )
                {
                    // unluckily any function name is accepted here
                    $methods[] = "$classname::*";
                }
                else
                {
                    // load file if defined, else use autoload (same as ezjsc does)
                    if ( $ini->hasVariable( $blockname, 'File' ) )
                        include_once( $ini->variable( $blockname, 'File' ) );
                    // get class name if defined, else use first argument as class name
                    if ( $ini->hasVariable( $blockname, 'Class' ) )
                        $realclassname = $ini->variable( $blockname, 'Class' );
                    else
                        $realclassname = $classname;

                    if ( class_exists( $realclassname ) )
                    {
                        $reflectionClass = new ReflectionClass( $realclassname );
                        foreach( $reflectionClass->getMethods() as $reflectionMethod )
                        {
                            if ( $reflectionMethod->isStatic() )
                            {
                                $methods[] = "$classname::" . $reflectionMethod->name;
                            }
                        }
                    }
                }
            }
        }
        return $methods;
    }

    static function methodSignature( $params )
    {
        // use ezjscServerRouter::instance() to ee if method exists, Reflection for details (???)
        throw new Exception( 'Not implemented yet' );
    }

    static function methodHelp( $params )
    {
        // use ezjscServerRouter::instance() to ee if method exists, Reflection for help from javadoc
        throw new Exception( 'Not implemented yet' );
    }

}

?>