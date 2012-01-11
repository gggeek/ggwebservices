<?php
/**
 * Implementation of the webservices for eZDebug: immplement the system.* ones
 * that are standfard for xmlrpc
 *
 * @version $Id$
 * @author G. Giunta
 * @copyright (C) 2010-2012 G. Giunta
 * @license code licensed under the GNU GPL 2.0: see README
 *
 */

class ggwebservicesJSCFunctions
{

    /**
    * Returns the list of all webservices available on this server
    * @return array
    */
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
                    else
                    {
                        /// @todo log config error
                    }
                }
            }
        }
        return $methods;
    }

    /**
     * Returns the signature of a given webservice.
     * Not implemented currently for eZJSCore, and it probably nevr will, due to
     * the uderlying API.
     * @param string className
     * @param string methodName
     * @return array
     */
    static function methodSignature( $params )
    {
        // use ezjscServerRouter::instance() to ee if method exists, Reflection for details (???)
        throw new Exception( 'Not implemented yet' );
    }

    /**
    * Returns a help text descibing a given webservice.
    * The help text is generated via introspection from phpdoc comments on the source code.
    * Note that it takes 2 params insted of 1 as this helps clients sending requests:
    * it is hard for ezjscore to send a string param containing two colons in a row...
    * @param string className
    * @param string methodName
    * @return string
    */
    static function methodHelp( $params )
    {
        // we can not use ezjscServerRouter::getInstance() to see if method exists,
        // because it checks permissions!

        if ( count( $params ) != 2 )
        {
            throw new Exception( ggWebservicesServer::INVALIDPARAMSERROR . ' ' . ggWebservicesServer::INVALIDPARAMSSTRING );
        }
        $className = array_shift( $params );
        $functionName = array_shift( $params );
        $ini = eZINI::Instance( 'ezjscore.ini' );
        if ( $ini->hasGroup( 'ezjscServer_' . $className ) )
        {
            if ( $ini->hasVariable( 'ezjscServer_' . $className, 'File' ) )
                include_once( $ini->variable( 'ezjscServer_' . $className, 'File' ) );

            if ( $ini->hasVariable( 'ezjscServer_' . $className, 'TemplateFunction' ) )
            {
                if ( $ini->variable( 'ezjscServer_' . $className, 'TemplateFunction' ) === 'true' )
                {
                    return 'No description can be given: method implemented via a template';
                }
            }
            if ( $ini->hasVariable( 'ezjscServer_' . $className, 'Class' ) )
            {
                $realclassname = $ini->variable( 'ezjscServer_' . $className, 'Class' );
            }
            else
            {
                $realclassname = $className;
            }
            if ( class_exists( $realclassname ) )
            {
                $reflectionClass = new ReflectionClass( $realclassname );
                $reflectionMethod = $reflectionClass->getMethod( $functionName );
                if ( is_object( $reflectionMethod ) && $reflectionMethod->isStatic() )
                {
                    $doc = $reflectionMethod->getDocComment();
                    // Clean up a bit the phpdoc format
                    $doc = preg_replace( '#^(/\*?)#', '', $doc ); // opening comment
                    $doc = preg_replace( '#(\*/)$#', '', $doc ); // end comment
                    $doc = preg_replace( '#^( *\*)#m', '', $doc ); // star on begin of line
                    $doc = preg_replace( '#(\* *)$#m', '', $doc ); // star on end of line
                    return $doc;
                }
            }
            else
            {
                /// @todo log config error
            }
        }

        throw new Exception( ggWebservicesServer::INVALIDINTROSPECTIONERROR . ' ' . ggWebservicesServer::INVALIDINTROSPECTIONSTRING );
    }

}

?>