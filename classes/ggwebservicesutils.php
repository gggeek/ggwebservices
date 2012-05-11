<?php
/**
 * Generic class used to takes adventage of changes in 4.3 relatives to translation
 * and template system.
 *
 * @author Carlos Revillo
 * @copyright (C) 2009-2012 Carlos Revillo
 */

class ggWebservicesUtils
{
    /**
     * Abstract method to translate labels and eventually takes advantage of new 4.3 i18n API
     * @param $context
     * @param $message
     * @param $comment
     * @param $argument
     * @return string
     */
    public static function ezpI18ntr( $context, $message, $comment = null, $argument = null )
    {
        $translated = '';
        
        // eZ Publish < 4.3 => use old i18n system
        if( eZPublishSDK::majorVersion() >= 4 && eZPublishSDK::minorVersion() < 3 )
        {
            if( !function_exists( 'ezi18n' ) )
                include_once( 'kernel/common/i18n.php' );
            
            $translated = ezi18n( $context, $message, $comment, $argument );
        }
        else
        {
            $translated = ezpI18n::tr( $context, $message, $comment, $argument );
        }
        
        return $translated;
    }
    
    /**
     * Abstract method to initialize a template and eventually takes advantage of new 4.3 TPL API
     * @return eZTemplate
     */
    public static function eZTemplateFactory()
    {
        $tpl = null;
        if(eZPublishSDK::majorVersion() >= 4 && eZPublishSDK::minorVersion() < 3)
        {
            include_once( 'kernel/common/template.php' );
            $tpl = templateInit();
        }
        else
        {
            $tpl = eZTemplate::factory();
        }
        
        return $tpl;
    }
}
