<?php
/**
 * Class used to communicate with soap servers
 *
 * @author G. Giunta
 * @copyright (C) 2009-2015 G. Giunta
 */

class ggSOAPClient extends ggWebservicesClient
{
    /**
     * Sends a soap message and returns the response object.
     * NB: we define extra headers here and not in request obj because here we
     *     switch between soap 1.1 and 1.2 protocols in the client - but we need
     *     the request's name+ns for creating the soap 1.1 header...
     *     This could be probably be pushed down unto the request anyway
     * @param ggSOAPRequest $request
     * @return ggSOAPResponse
     * @todo raise an error if the request is not a soap one and has no ->ns() method
    */
    function send( $request )
    {
        if ( $this->SoapVersion != 0 )
        {
            $request->setSOAPVersion( $this->SoapVersion );
        }
        return parent::send( $request );
    }

    public function setOption( $option, $value )
    {
        switch( $option )
        {
            case 'soapVersion':
                $this->SoapVersion = $value;
                return true;
            default:
                return parent::setOption( $option, $value );
        }
    }

    /// @todo move this to constructor
    function availableOptions()
    {
        return array_merge( $this->Options, array( 'soapVersion' ) );
    }

    function getOption( $option )
    {
        switch( $option )
        {
            case 'soapVersion':
                return $this->SoapVersion;
            default:
                return parent::getOption( $option );
        }
    }

    /// 1 for SOAP_1_1, 2 for SOAP_1_2
    public function setSoapVersion( $version )
    {
        $this->SoapVersion = $version;
    }

    // by default do not enforce a SOAP version upon requests
    protected $SoapVersion = 0;

    protected $UserAgent = 'gg eZ SOAP client';
    protected $ResponseClass = 'ggSOAPResponse';
}

?>