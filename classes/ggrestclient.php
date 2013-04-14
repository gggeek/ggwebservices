<?php
/**
 * Class used to communicate with 'REST' servers
 * @deprecated use a plain ggWebservicesClient instead of this
 *
 * @author G. Giunta
 * @copyright (C) 2009-2013 G. Giunta
 */

class ggRESTClient extends ggWebservicesClient
{
    /*function __construct( $server, $path = '/', $port = 80, $protocol = null )
    {
        $this->ResponseClass = 'ggRESTResponse';
        $this->UserAgent = 'gg eZ REST client';
        //$this->Verb = 'GET';
        parent::__construct( $server, $path, $port, $protocol );
    }*/

    /// NB: this assumes we're sending a ggRESTrequest
    /// @todo do not error if we're sending a plain request (test if methods exist)
    function send( $request )
    {
        if ( $this->Verb != '' )
        {
            $request->setMethod( $this->Verb );
        }
        // use strict comparison, so that setting it to '' by the end user will work
        if ( $this->NameVar !== null )
        {
            $request->setNameVar( $this->NameVar );
        }
        if ( $this->ResponseType !== null )
        {
            $request->setResponseType( $this->ResponseType );
        }
        if ( $this->RequestType !== null )
        {
            $request->setContentType( $this->RequestType );
        }
        return parent::send( $request );
    }

    public function setOption( $option, $value )
    {
        if ( $option == 'nameVariable' )
        {
            $this->NameVar = $value;
        }
        else if ( $option == 'responseType' )
        {
            $this->ResponseType = $value;
        }
        else if ( $option == 'requestType' )
        {
            $this->RequestType = $value;
        }
        else
        {
            return parent::setOption( $option, $value );
        }
    }

    // store this in the client to inject it in requests
    protected $NameVar = null;
    // also injected in the requests if not null
    protected $ResponseType = null;
    // this one too. In the request it is actually called ContentType, but the
    // default ContenType in the client is not null, so we need an extra variable
    protected $RequestType = null;

    // override default value from ggwsclient: if the user sets this via a
    // setOption call, we will later inject it into the request object
    protected $Verb = null;

    protected $ResponseClass = 'ggRESTResponse';
    protected $UserAgent = 'gg eZ REST client';
}

?>