<?php

class ggwebservicesInfo
{
    static function info()
    {
        return array(
            'Name' => "GG Webservices extension",
            'Version' => "0.6-dev",
            'Copyright' => "Copyright (C) 2009-2010 Gaetano Giunta",
            'License' => "GNU General Public License v2.0",
            'Includes the following third-party software' => array(
                'Name' => 'YUI',
                'Version' => "2.5.0",
                'Copyright' => 'Copyright (c) 2010, Yahoo! Inc. All rights reserved.',
                'License' => 'Licensed under the BSD License' ),
            'Includes the following third-party software (2)' => array(
                'Name' => 'phpxmlrpc',
                'Version' => "3.0.0.beta",
                'Copyright' => 'Copyright (c) 1999,2000,2002 Edd Dumbill.',
                'License' => 'Licensed under the BSD License' ),
            'Includes the following third-party software (3)' => array(
                'Name' => 'jQuery JSON Plugin',
                'Version' => "2.1",
                'Copyright' => 'Brantley Harris (?)',
                'License' => 'MIT License' )
                /*'nuSOAP' => array(
                    'Version' => '2008-04-06',
                    'License' => 'GNU/LGPL  v2.1 - Copyright (c) 2002 NuSphere Corporation',
                    'For more information' => 'http://sourceforge.net/projects/nusoap' ),*/
        );
    }
}

?>