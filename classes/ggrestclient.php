<?php
/**
 * Class used to communicate with 'REST' servers
 * @deprecated use a plain ggWebservicesClient instead of this
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggRESTClient extends ggWebservicesClient
{
    function __construct( $server, $path = '/', $port = 80, $protocol = null )
    {
        $this->ResponseClass = 'ggRESTResponse';
        $this->UserAgent = 'gg eZ REST client';
        $this->Verb = 'GET';
        parent::__construct( $server, $path, $port, $protocol );
    }

    /// NB: this assumes we're sending a ggRESTrequest
    /// @todo do not error if we're sending a plain request (test if methods exist)
    function send( $request )
    {
        if ( $this->Verb != '' )
        {
            $request->setMethod( $this->SoapVersion );
        }
        // use strict comparison, so that setting it to '' by the end user will work
        if ( $this->NameVar !== null )
        {
            $request->setNameVar( $this->NameVar );
        }
        return parent::send( $request );
    }

    public function setOption( $option, $value )
    {
        if ( $option = 'nameVariable' )
        {
            $this->NameVar = $version;
        }
        else
        {
            parent::setOption( $option, $value );
        }
    }

    // store this in the client to inject it in requests
    protected $NameVar = null;
    // override default value from ggwsclient: if the user sets this via a
    // setOption call, we will later inject it into the request object
    protected $Verb = null;
}

?>