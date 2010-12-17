<?php
/**
 * Class used to communicate with soap servers
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009-2010
 */

class ggSOAPClient extends ggWebservicesClient
{
    /// @deprecated the ggWebservicesClient parent class can do by itself all of this
    /*function __construct( $server, $path = '/', $port = 80, $protocol=null )
    {
        $this->ResponseClass = 'ggSOAPResponse';
        $this->UserAgent = 'gg eZ SOAP client';
        //$this->ContentType = 'text/xml';
        parent::__construct( $server, $path, $port, $protocol );
    }*/

    /**
      Sends a soap message and returns the response object.
      NB: we define extra headers here and not in request obj because here we
          switch between soap 1.1 and 1.2 protocols in the client - but we need
          the request's name+ns for creating the soap 1.1 header...
          This could be probably be pushed down unto the request anyway
      @todo raise an error if the request is not a soap one and has no ->ns() method
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
        if ( $option == 'soapVersion' )
        {
             $this->SoapVersion = $value;
        }
        else
        {
            return parent::setOption( $option, $value );
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