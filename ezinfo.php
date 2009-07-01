<?php

class ggwebservicesInfo
{
    static function info()
    {
        return array(
            'Name' => "GG Webservices extension",
            'Version' => "0.4",
            'Copyright' => "Copyright (C) 2009 Gaetano Giunta",
            'License' => "GNU General Public License v2.0",
            'Includes the following third-party software' => array(
                'phpxmlrpc' => 'http://phpxmlrpc.sourceforge.net/',
                /*'nuSOAP' => array(
                    'Version' => '2008-04-06',
                    'License' => 'GNU/LGPL  v2.1 - Copyright (c) 2002 NuSphere Corporation',
                    'For more information' => 'http://sourceforge.net/projects/nusoap' ),*/
               )
        );
    }
}

?>