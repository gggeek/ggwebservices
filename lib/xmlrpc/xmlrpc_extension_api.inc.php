<?php
/**
 * Try to implement the same API of the PHP built-in XMLRPC extension, so that
 * projects relying on it can be ported to php installs where the extension is
 * missing.
 *
 * @author Gaetano Giunta
 * @copyright (c) 2006-2022 G. Giunta
 * @license code licensed under the BSD License: see license.txt
 *
 * @requires phpxmlrpc version 2.1 or later
 *
 * Known differences from the observed behaviour of the PHP extension:
 * - php arrays indexed with integer keys starting above zero or whose keys are
 *   not in a strict sequence will be converted into xmlrpc structs, not arrays
 * - php arrays indexed with mixed string/integer keys will preserve the integer
 *   keys in the generated structs
 * - base64 and datetime values are converted (by set_type(), decode(), decode_request())
 *   into slightly different php objects - but std object members are preserved
 * - a single NULL value passed to xmlrpc_encode_req(null, $val) will be decoded as '', not NULL
 *   (the extension generates an invalid xmlrpc response in this case)
 * - the native extension truncates double values to 6 decimal digits, we do not
 *
 * @todo finish implementation of 3 missing functions
 */

// requires: xmlrpc.inc
// requires: xmlrpcs.inc

if (!in_array('xmlrpc', get_loaded_extensions()) && !function_exists('xmlrpc_decode'))
{
    /**
     * Decode the xml generated by xmlrpc_encode() into native php values
     * @param string $xml
     * @param string $encoding
     * @return mixed
     *
     * @todo implement usage of $encoding
     */
    function xmlrpc_decode($xml, $encoding = '')
    {
        // strip out unnecessary xml in case we're deserializing a single param.
        // in case of a complete response, we do not have to strip anything
        // please note that the test below has LARGE space for improvement (eg it might trip on xml comments...)
        if (strpos($xml, '<methodResponse>') === false)
            $xml = preg_replace(array('!\s*<params>\s*<param>\s*!', '!\s*</param>\s*</params>\s*$!'), array('', ''), $xml);
        $val = php_xmlrpc_decode_xml($xml);
        if (!$val) {
            return null; // instead of false
        }
        if (is_a($val, 'xmlrpcresp')) {
            if ($fc = $val->faultCode()) {
                return array('faultCode' => $fc, 'faultString' => $val->faultString());
            } else {
                return php_xmlrpc_decode($val->value(), array('extension_api'));
            }
        } else
            return php_xmlrpc_decode($val, array('extension_api'));
    }

    /**
     * Decode an xmlrpc request (or response) into php values
     * @param string $xml
     * @param string $method (will not be set when decoding responses)
     * @param string $encoding not yet used
     * @return mixed
     *
     * @todo implement usage of $encoding
     */
    function xmlrpc_decode_request($xml, &$method, $encoding = '')
    {
        $val = php_xmlrpc_decode_xml($xml);
        if (!$val) {
            return null; // instead of false
        }
        if (is_a($val, 'xmlrpcresp')) {
            if ($fc = $val->faultCode()) {
                $out = array('faultCode' => $fc, 'faultString' => $val->faultString());
            } else {
                $out = php_xmlrpc_decode($val->value(), array('extension_api'));
            }
        } else if (is_a($val, 'xmlrpcmsg')) {
            $method = $val->method();
            $out = array();
            $pn = $val->getNumParams();
            for ($i = 0; $i < $pn; $i++)
                $out[] = php_xmlrpc_decode($val->getParam($i), array('extension_api'));
        } else
            return null; /// @todo test lib behaviour in this case

        return $out;
    }

    /**
     * Given a PHP val, convert it to xmlrpc code (wrapped up in params/param elements).
     * @param mixed $val
     * @return string
     */
    function xmlrpc_encode($val)
    {
        $val = php_xmlrpc_encode($val, array('extension_api'));
        return "<?xml version=\"1.0\" ?" . ">\n<params>\n<param>\n" . $val->serialize() . "</param>\n</params>";
    }

    /**
     * Given a method name and array of php values, create an xmlrpc request out
     * of them. If method name === null, will create an xmlrpc response
     * @param string $meth
     * @param array $vals
     * @param array $opts options array
     * @return string
     *
     * @todo implement parsing/usage of options
     */
    function xmlrpc_encode_request($meth, $vals, $opts = null)
    {
        $opts = array_merge($opts, array('extension_api'));

        if ($meth !== null) {
            // mimic EPI behaviour: if ($val === NULL) then send NO parameters
            if (!is_array($vals)) {
                if ($vals === NULL) {
                    $vals = array();
                } else {
                    $vals = array($vals);
                }
            } else {
                // if given a 'hash' array, encode it as a single param
                $i = 0;
                $ok = true;
                foreach ($vals as $key => $value)
                    if ($key !== $i) {
                        $ok = false;
                        break;
                    } else
                        $i++;
                if (!$ok) {
                    $vals = array($vals);
                }
            }
            $values = array();
            foreach ($vals as $key => $value) {
                $values[] = php_xmlrpc_encode($value, $opts);
            }

            // create request
            $req = new xmlrpcmsg($meth, $values);
            $resp = $req->serialize();
        } else {
            // create response
            if (is_array($vals) && xmlrpc_is_fault($vals))
                $req = new xmlrpcresp(0, (integer)$vals['faultCode'], (string)$vals['faultString']);
            else
                $req = new xmlrpcresp(php_xmlrpc_encode($vals, $opts));
            $resp = "<?xml version=\"1.0\"?" . ">\n" . $req->serialize();
        }
        return $resp;
    }

    /**
     * Given a php value, return its corresponding xmlrpc type
     * @param mixed $val
     * @return string
     */
    function xmlrpc_get_type($val)
    {
        switch (strtolower(gettype($val))) {
            case 'string':
                return $GLOBALS['xmlrpcString'];
            case 'integer':
            case 'resource':
                return $GLOBALS['xmlrpcInt'];
            case 'double':
                return $GLOBALS['xmlrpcDouble'];
            case 'boolean':
                return $GLOBALS['xmlrpcBoolean'];
            case 'array':
                $i = 0;
                $ok = true;
                foreach ($val as $key => $value)
                    if ($key !== $i) {
                        $ok = false;
                        break;
                    } else
                        $i++;

                return $ok ? $GLOBALS['xmlrpcArray'] : $GLOBALS['xmlrpcStruct'];
            case 'object':
                if (is_a($val, 'xmlrpcval')) {
                    list($type, $val) = each($val->me);
                    return str_replace(array('i4', 'dateTime.iso8601'), array('int', 'datetime'), $type);
                }
                return $GLOBALS['xmlrpcStruct'];
            case 'null':
                return $GLOBALS['xmlrpcBase64']; // go figure why...
        }
    }

    /**
     * Set string $val to a known xmlrpc type (base64 or datetime only), for serializing it later
     * (NB: this will turn the string into an object!).
     * @param string $val
     * @param string $type
     * @return boolean false if conversion did not take place
     */
    function xmlrpc_set_type(&$val, $type)
    {
        if (is_string($val)) {
            if ($type == 'base64') {
                $val = new xmlrpcval($val, 'base64');
                // add two object members to make it more compatible to user code
                $val->scalar = $val->me['base64'];
                $val->xmlrpc_type = 'base64';
            } elseif ($type == 'datetime') {
                if (preg_match('/([0-9]{8})T([0-9]{2}):([0-9]{2}):([0-9]{2})/', $val)) {
                    $val = new xmlrpcval($val, 'dateTime.iso8601');
                    // add 3 object members to make it more compatible to user code
                    $val->scalar = $val->me['dateTime.iso8601'];
                    $val->xmlrpc_type = 'datetime';
                    $val->timestamp = iso8601_decode($val->scalar);
                } else {
                    return false;
                }
            } else {
                // @todo EPI will NOT raise a warning for good type names, eg. 'boolean', etc...
                trigger_error("invalid type '$type' passed to xmlrpc_set_type()");
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a given php array corresponds to an xmlrpc fault response
     * @param array $arr
     * @return boolean
     */
    function xmlrpc_is_fault($arr)
    {
        return is_array($arr) && array_key_exists('faultCode', $arr) && array_key_exists('faultString', $arr);
    }

    /** Server side ***************************************************************/

    /**
     * Create a new xmlrpc server instance
     * @return xmlrpc_server
     */
    function xmlrpc_server_create()
    {
        $s = new xmlrpc_server();
        $s->functions_parameters_type = 'epivals';
        $s->compress_response = false; // since we will not be outputting any http headers to go with it
        return $s;
    }

    /**
     * This function acutally does nothing, but it is kept for compatibility.
     * To destroy a server object, just unset() it, or send it out of scope...
     * @param xmlrpc_server $server
     * @return integer
     */
    function xmlrpc_server_destroy($server)
    {
        if (is_a($server, 'xmlrpc_server'))
            return 1;
        else
            return 0;
    }

    /**
     * Add a php function as xmlrpc method handler to an existing server.
     * PHP function sig: f(string $methodname, array $params, mixed $extra_data)
     * @param xmlrpc_server $server
     * @param string $method_name
     * @param string $function
     * @return boolean true on success or false
     */
    function xmlrpc_server_register_method($server, $method_name, $function)
    {
        if (is_a($server, 'xmlrpc_server')) {
            $server->add_to_map($method_name, $function);
            return true;
        } else
            return false;
    }

    /**
     * Parses XML request and calls corresponding method
     * @param xmlrpc_server $server
     * @param string $xml
     * @param mixed $user_data
     * @param array $output_options
     * @return string
     */
    function xmlrpc_server_call_method($server, $xml, $user_data, $output_options = null)
    {
        $server->user_data = $user_data;
        return $server->service($xml, true);
    }

    function xmlrpc_parse_method_descriptions($xml)
    {
        return array();
    }

    function xmlrpc_server_add_introspection_data($server, $desc)
    {
        return 0;
    }

    function xmlrpc_server_register_introspection_callback($server, $function)
    {
        return false;
    }
}
