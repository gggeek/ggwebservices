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
    /**
     * NB: this assumes we're sending a ggRESTRequest
     * @param ggRESTRequest $request
     * @return ggRESTResponse
     * @todo do not error if we're sending a plain request? (test if methods exist)
     */
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
        if ( $this->Accept !== null )
        {
            $request->setAccept( $this->Accept );
        }
        if ( count( $this->RequestHeaders ) )
        {
            foreach( $this->RequestHeaders  as $name => $value )
            {
                $request->setExtraHeader( $name, $value );
            }
        }
        return parent::send( $request );
    }

    public function setOption( $option, $value )
    {
        switch( $option )
        {
            case 'nameVariable':
                $this->NameVar = $value;
                return true;
            case 'responseType':
                $this->ResponseType = $value;
                return true;
            case 'requestType':
                $this->RequestType = $value;
                return true;
            case 'accept':
                $this->Accept = $value;
                return true;
            case 'requestHeaders':
                $this->RequestHeaders = $value;
                return true;
            default:
                return parent::setOption( $option, $value );
        }
    }

    /// @todo move this to constructor
    function availableOptions()
    {
        return array_merge( $this->Options, array( 'nameVariable', 'responseType', 'requestType', 'accept', 'RequestHeaders' ) );
    }

    function getOption( $option )
    {
        switch( $option )
        {
            case 'nameVariable':
                return $this->NameVar;
            case 'responseType':
                return $this->ResponseType;
            case 'requestType':
                return $this->RequestType;
            case 'accept':
                return $this->Accept;
            case 'requestHeaders':
                return $this->RequestHeaders;
            default:
                return parent::getOption( $option );
        }
    }

    function setRequestHeader( $name, $value )
    {
        $this->RequestHeaders[$name] = $value;
    }

    // store this in the client to inject it in requests
    protected $NameVar = null;
    // also injected in the requests if not null
    protected $ResponseType = null;
    // this one too. In the request it is actually called ContentType, but the
    // default ContentType in the client is not null, so we need an extra variable
    protected $RequestType = null;
    // this one too.
    protected $Accept = null;

    // override default value from ggwsclient: if the user sets this via a
    // setOption call, we will later inject it into the request object
    protected $Verb = null;

    protected $ResponseClass = 'ggRESTResponse';
    protected $UserAgent = 'gg eZ REST client';
}

?>