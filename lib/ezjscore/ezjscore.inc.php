<?php
/**
 * eZJSCORE extension to the PHP-XMLRPC lib
 *
 * For more info see:
 * http://projects.ez.no/ezjscore
 *
 * @version $Id$
 * @author Gaetano Giunta
 * @copyright (c) 2010 G. Giunta
 * @license code licensed under the GNU GPL 2.0 License
 *
 **/

	// requires: xmlrpc.inc 2.0 or later

	class ezjscore_client extends xmlrpc_client
	{
		// by default, no multicall exists for ezjscore, so do not try it
		var $no_multicall = true;

		// default return type of calls to ezjscore servers: decoded data
		var $return_type = 'phpvals';

	    // default (preferred) return type
	    var $accepted_content_type = array( 'application/json' );
	}


	class ezjscoremsg extends xmlrpcmsg
	{

		var $content_type = 'application/x-www-form-urlencoded';

		/**
		* @param string $meth the name of the method to invoke
		* @param array $pars array of parameters to be passed to the method (xmlrpcval objects)
		*/
		function ezjscoremsg($meth, $pars=0)
		{
		    $this->methodname=$meth;
		    if(is_array($pars))
		    {
		        foreach($pars as $n => $p)
		        {
		            $this->addParam($p, $n);
		        }
		    }
		}

	    function addParam($par, $name='')
	    {
	        // add check: do not add to self params which are not xmlrpcvals
	        if(is_object($par) && is_a($par, 'xmlrpcval'))
	        {
	            if ( $name=='')
	            {
	                $this->params[]=$par;
	            }
	            else
	            {
	                $this->params[$name]=$par;
	            }
	            return true;
	        }
	        else
	        {
	            return false;
	        }
	    }

		/**
		* @access private
		*/
		function createPayload($charset_encoding='')
		{
			if ($charset_encoding != '')
				$this->content_type = 'application/x-www-form-urlencoded; charset=' . $charset_encoding;
			else
				$this->content_type = 'application/x-www-form-urlencoded';
			$this->payload = "ezjscServer_function_arguments=".urlencode($this->methodname);
			foreach($this->params as $n => $p)
			{
				$this->payload .= "&$name=" . serialize_ezjscoreval($p, $charset_encoding);;
			}
		}

		/**
		* Parse the ezjscore response contained in the string $data and return a ezjscoreresp object.
		* @param string $data the xmlrpc response, eventually including http headers
		* @param bool $headers_processed when true prevents parsing HTTP headers for interpretation of content-encoding and consequent decoding
		* @param string $return_type decides return type, i.e. content of response->value(). Only 'phpvals' and 'raw' supported for now
		* @return jsonrpcresp...
		* @access private
		*/
		function &parseResponse($data='', $headers_processed=false, $return_type='phpvals')
		{
			if($this->debug)
			{
				print "<PRE>---GOT---\n" . htmlentities($data) . "\n---END---\n</PRE>";
			}

			if($data == '')
			{
				error_log('XML-RPC: '.__METHOD__.': no response received from server.');
				$r = new ezjscoreresp(0, $GLOBALS['xmlrpcerr']['no_data'], $GLOBALS['xmlrpcstr']['no_data']);
				return $r;
			}

			$GLOBALS['_xh']=array();

			$raw_data = $data;
			// parse the HTTP headers of the response, if present, and separate them from data
			if(substr($data, 0, 4) == 'HTTP')
			{
				$r =& $this->parseResponseHeaders($data, $headers_processed);
				if ($r)
				{
					// parent class implementation of parseResponseHeaders returns in case
					// of error an object of the wrong type: recode it into correct object
					$rj = new ezjscoreresp(0, $r->faultCode(), $r->faultString());
					$rj->raw_data = $data;
					return $rj;
				}
			}
			else
			{
				$GLOBALS['_xh']['headers'] = array();
				$GLOBALS['_xh']['cookies'] = array();
			}

			if($this->debug)
			{
                /// @todo: decode ezjscore real debug format
				$start = strpos($data, '/* SERVER DEBUG INFO (BASE64 ENCODED):');
				if ($start !== false)
				{
					$start += strlen('/* SERVER DEBUG INFO (BASE64 ENCODED):');
					$end = strpos($data, '*/', $start);
					$comments = substr($data, $start, $end-$start);
					print "<PRE>---SERVER DEBUG INFO (DECODED) ---\n\t".htmlentities(str_replace("\n", "\n\t", base64_decode($comments)))."\n---END---\n</PRE>";
				}
			}

			// if user wants back raw response, give it to him
			if ($return_type == 'raw')
			{
				$r = new ezjscoreresp($data, 0, '', 'raw');
				$r->hdrs = $GLOBALS['_xh']['headers'];
				$r->_cookies = $GLOBALS['_xh']['cookies'];
				$r->raw_data = $raw_data;
				return $r;
			}

		    // if not raw data, we assume phpvals are wanted

		    // try to decode based on what the server said the response is
		    $ct = @$GLOBALS['_xh']['headers']['content-type'];
		    // remove charset info
		    $ct = split(';', $ct);
		    $ct = $ct[0];
		    switch($ct)
		    {
		        // std return type from ezjscore: text/javascript for json, text/xml for xml
		        case 'text/javascript':
		        case 'application/json':
		            $r = json_decode( $data, true );
		            if ( function_exists( 'json_last_error' ) )
		            {
		                $err = json_last_error();
		            }
		            else
		            {
		                $err = ( $val === null ) ? 1 : false;
		            }
		            if ( $err )
		            {
		                $r = new ezjscoreresp(0, $GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcerrstr']['invalid_return'] . ". Json decoding error: $err");
		            }
		            else
		            {
		                if ($this->debug)
		                {
		                    print "<PRE>---PARSED---\n" ;
		                    var_export($r);
		                    print "\n---END---</PRE>";
		                }
		                if (!is_array($r) || !isset($r['error_text']) || !isset($r['content']))
		                {
		                    $r = new ezjscoreresp(0, $GLOBALS['xmlrpcerr']['invalid_return'], $GLOBALS['xmlrpcerrstr']['invalid_return'] . " (response does not contain 'error_text' and 'content' members");
		                }
		                else
		                {
		                    if ( $r['error_text'] != '' )
		                    {
		                        $r = new ezjscoreresp(0, $GLOBALS['xmlrpcerr']['server_error'], $r['error_text']);
		                    }
		                    else
		                    {
		                        $r = new ezjscoreresp($r['content'], 0, '', 'phpvals');
		                    }
		                }
		            }
		            break;
		        case 'text/xml':
		        case 'application/xml':
    		    default:
    		        $r = new ezjscoreresp(0, $GLOBALS['xmlrpcerr']['cannot-decompress'], "Unsupported feature: decoding response in '$ct' format" );
		    }
		    $r->hdrs = $GLOBALS['_xh']['headers'];
		    $r->_cookies = $GLOBALS['_xh']['cookies'];
		    $r->raw_data = $raw_data;
		    $r->content_type = $ct;
		    return $r;
		}
	}

	class ezjscoreresp extends xmlrpcresp
	{
		var $content_type = 'text/javascript';

		/**
		* Returns textual representation of the response.
		* @param string $charset_encoding the charset to be used for serialization. if null, US-ASCII is assumed
		* @return string the json representation of the response
		* @access public
		*/
		function serialize($charset_encoding='')
		{
			if ($charset_encoding != '')
				$this->content_type = $this->content_type . '; charset=' . $charset_encoding;
			else
				$this->content_type = $this->content_type;
			$this->payload = serialize_ezjscoreresp($this, $charset_encoding, $this->content_type);
			return $this->payload;
		}

	}

	class ezjscoreval extends xmlrpcval
	{
		/**
		* Returns json representation of the value.
		* @param string $charset_encoding the charset to be used for serialization. if null, US-ASCII is assumed
		* @return string
		* @access public
		*/
		function serialize($charset_encoding='')
		{
			return serialize_ezjscoreval($this, $charset_encoding);
		}
	}

	/**
	* Serialize an ezjscoreresp (or xmlrpcresp/jsonrpcresp ?) as json.
	* Moved outside of the corresponding class to ease multi-serialization of
	* xmlrpcresp objects
	* @param xmlrpcresp or jsonrpcresp $resp
	* @param mixed $id
	* @return string
	* @access private
	*
	* @todo support xml format, too
	*/
	function serialize_ezjscoreresp($resp, $charset_encoding='', $format='application/json')
	{
	    switch($format)
	    {
	        case 'text/javascript':
	        case 'application/json':
    	        if($resp->errno)
    	        {
        	        $result = json_encode( array( 'error_text' => $resp->errno . ' ' . $resp->errstr, 'content' => ''));
    	        }
    	        else
    	        {
    	            if ($resp->valtyp == 'phpvals')
    	            {
    	                $result = json_encode( array( 'error_text' => '', 'content' => $resp->val));
    	            }
    	            else
    	            {
    	                die("cannot currently serialize ezjscoreresp objects whose content is not native php values: " . $resp->valtyp);
    	            }
    	        }
	            break;
	        default:
	            die("cannot currently serialize ezjscoreresp objects to formats other than json: $format");
	    }
		return $result;
	}

	/**
	* Serialize an ezjscoreval (or xmlrpcval) as application/x-www-form-urlencoded.
	* Recursive stuff is handles as json (@todo: to be verified...)
	* Moved outside of the corresponding class to ease multi-serialization of
	* xmlrpcval/jsonrpcval objects
	* @param xmlrpcval or jsonrpcval $value
	* @string $charset_encoding
	* @access private
	*/
	function serialize_ezjscoreval($value, $charset_encoding='')
	{
		reset($value->me);
		list($typ, $val) = each($value->me);

		$rs = '';
		switch(@$GLOBALS['xmlrpcTypes'][$typ])
		{
			case 1:
				switch($typ)
				{
					case $GLOBALS['xmlrpcString']:
						$rs .= urlencode($val);
						break;
					case $GLOBALS['xmlrpcI4']:
					case $GLOBALS['xmlrpcInt']:
						$rs .= (int)$val;
						break;
					case $GLOBALS['xmlrpcDateTime']:
						// send date as string.
						$rs .= urlencode($val);
						break;
					case $GLOBALS['xmlrpcDouble']:
						// add a .0 in case value is integer.
						// This helps us carrying around floats in js, and keep them separated from ints
						$sval = strval((double)$val); // convert to string
						// fix usage of comma, in case of eg. german locale
						$sval = str_replace(',', '.', $sval);
						if (strpos($sval, '.') !== false || strpos($sval, 'e') !== false)
						{
							$rs .= $sval;
						}
						else
						{
							$rs .= $val.'.0';
						}
						break;
					case $GLOBALS['xmlrpcBoolean']:
						$rs .= ($val ? 'true' : 'false');
						break;
					case $GLOBALS['xmlrpcBase64']:
						// treat base 64 values as strings ???
						$rs .= base64_encode($val);
						break;
					default:
						$rs .= "null";
				}
				break;
			case 2:
				// array or struct: sent as json
				$rs .= "[";
				$len = sizeof($val);
				if ($len)
				{
					for($i = 0; $i < $len; $i++)
					{
						$rs .= serialize_ezjscoreval($val[$i], $charset_encoding);
						$rs .= ",";
					}
					$rs = substr($rs, 0, -1) . "]";
				}
				else
				{
					$rs .= "]";
				}
				break;
			case 3:
				// struct
				foreach($val as $key2 => $val2)
				{
					$rs .= ',"'.json_encode_entities($key2, null, $charset_encoding).'":';
					$rs .= serialize_ezjscoreval($val2, $charset_encoding);
				}
				$rs = '{' . substr($rs, 1) . '}';
				break;
			case 0:
				// let uninitialized jsonrpcval objects serialize to an empty string, as they do in xmlrpc land
				$rs = '""';
				break;
			default:
				break;
		}
		return $rs;
	}
