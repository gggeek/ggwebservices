<?php
/**
 * WS debugger:initialize user parameters received via GET (or POST): set defaults, cleanup
 *
 * @version $Id$
 * @author Gaetano Giunta
 * @copyright (C) 2005-2012 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 *
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 * @todo do some more sanitization of received parameters
 */

// work around magic quotes
  if (get_magic_quotes_gpc())
  {
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
                    array_map('stripslashes_deep', $value) :
                    stripslashes($value);

        return $value;
    }
    $_GET = array_map('stripslashes_deep', $_GET);
  }


  if ( isset( $_GET['usepost'] ) && $_GET['usepost'] === 'true' )
  {
      $_GET = $_POST;
  }

// recover input parameters
  $params['debug'] = false;
  $params['protocol'] = 0;
  $params['run'] = false;
  $params['wstype'] = 0;
  $params['id'] = '';
  if (isset($_GET['wsaction']))
  {
    if (isset($_GET['wstype']) && ($_GET['wstype'] == '1' || $_GET['wstype'] == '2' || $_GET['wstype'] == '3' || $_GET['wstype'] == '4'))
    {
      $params['wstype'] = $_GET['wstype'];
      // this is only unseful for jsonrpc, but anyway
      if (isset($_GET['id']))
        $params['id'] = $_GET['id'];
    }
    $params['host'] = isset($_GET['host']) ? $_GET['host'] : 'localhost'; // using '' will trigger an xmlrpc error...
    if (isset($_GET['protocol']) && ($_GET['protocol'] == '1' || $_GET['protocol'] == '2'))
      $params['protocol'] = $_GET['protocol'];
    if (strpos($params['host'], 'http://') === 0)
      $params['host'] = substr($params['host'], 7);
    else if (strpos($params['host'], 'https://') === 0)
    {
      $params['host'] = substr($host, 8);
      $params['protocol'] = 2;
    }
    $params['port'] = ( isset($_GET['port']) && $_GET['port'] != '' ) ? (int)$_GET['port'] : '';
    $params['path'] = isset($_GET['path']) ? $_GET['path'] : '';
    // in case user forgot initial '/' in xmlrpc server path, add it back
    if ($params['path'] && ($params['path'][0]) != '/')
      $params['path'] = '/'.$params['path'];

    if (isset($_GET['debug']) && ($_GET['debug'] == '1' || $_GET['debug'] == '2'))
      $params['debug'] = $_GET['debug'];

    $params['verifyhost'] = (isset($_GET['verifyhost']) && ($_GET['verifyhost'] == '1' || $_GET['verifyhost'] == '2')) ? $_GET['verifyhost'] : 0;
    if (isset($_GET['verifypeer']) && $_GET['verifypeer'] == '1')
      $params['verifypeer'] = true;
    else
      $params['verifypeer'] = false;
    $params['cainfo'] = isset($_GET['cainfo']) ? $_GET['cainfo'] : '';
    $params['proxy'] = isset($_GET['proxy']) ? $_GET['proxy'] : '';
    if (strpos($params['proxy'], 'http://') === 0)
      $params['proxy'] = substr($proxy, 7);
    $params['proxyuser'] = isset($_GET['proxyuser']) ? $_GET['proxyuser'] : '';
    $params['proxypwd'] = isset($_GET['proxypwd']) ? $_GET['proxypwd'] : '';
    $params['timeout'] = isset($_GET['timeout']) ? $_GET['timeout'] : 0;
    if (!is_numeric($params['timeout']))
      $params['timeout'] = 0;
    $params['action'] = $_GET['wsaction'];

    $params['method'] = isset($_GET['wsmethod']) ? $_GET['wsmethod'] : '';
    //$params['methodsig'] = isset($_GET['methodsig']) ? $_GET['methodsig'] : 0;
    $params['payload'] = isset($_GET['methodpayload']) ? $_GET['methodpayload'] : '';
    $params['alt_payload'] = isset($_GET['altmethodpayload']) ? $_GET['altmethodpayload'] : '';

    if (isset($_GET['run']) && $_GET['run'] == 'now')
      $params['run'] = true;

    $params['username'] = isset($_GET['username']) ? $_GET['username'] : '';
    $params['password'] = isset($_GET['password']) ? $_GET['password'] : '';

    $params['authtype'] = (isset($_GET['authtype']) && ($_GET['authtype'] == '2' || $_GET['authtype'] == '8')) ? $_GET['authtype'] : 1;

    if (isset($_GET['requestcompression']) && ($_GET['requestcompression'] == '1' || $_GET['requestcompression'] == '2'))
      $params['requestcompression'] = $_GET['requestcompression'];
    else
      $params['requestcompression'] = 0;
    if (isset($_GET['responsecompression']) && ($_GET['responsecompression'] == '1' || $_GET['responsecompression'] == '2' || $_GET['responsecompression'] == '3'))
      $params['responsecompression'] = $_GET['responsecompression'];
    else
      $params['responsecompression'] = 0;

    $params['clientcookies'] = isset($_GET['clientcookies']) ? $_GET['clientcookies'] : '';
    // soap
  	$params['wsdl'] = isset($_GET['wsdl']) ? (int)$_GET['wsdl'] : 0;
    $params['soapversion'] = isset($_GET['soapversion']) ? (int)$_GET['soapversion'] : 0;
    // rest
    $params['namevariable'] = isset($_GET['namevariable']) ? $_GET['namevariable'] :'';
    $params['responsetype'] = isset($_GET['responsetype']) ? $_GET['responsetype'] :'';
    $params['requesttype'] = isset($_GET['requesttype']) ? $_GET['requesttype'] :'';
    $params['verb'] = isset($_GET['verb']) ? $_GET['verb'] :'';
  }
  else
  {
    $params['host'] = '';
    $params['port'] = '';
    $params['path'] = '';
    $params['action'] = '';
    $params['method'] = '';
    $params['methodsig'] = 0;
    $params['payload'] = '';
    $params['alt_payload'] = '';
    $params['username'] = '';
    $params['password'] = '';
    $params['authtype'] = 1;
    $params['verifyhost'] = 0;
    $params['verifypeer'] = false;
    $params['cainfo'] = '';
    $params['proxy'] = '';
    $params['proxyuser'] = '';
    $params['proxypwd'] = '';
    $params['timeout'] = 0;
    $params['requestcompression'] = 0;
    $params['responsecompression'] = 0;
    $params['clientcookies'] = '';
    // soap
  	$params['wsdl'] = 0;
    $params['soapversion'] = 0;
    // rest
    $params['namevariable'] = '';
    $params['responsetype'] = '';
    $params['requesttype'] = '';
    $params['verb'] = '';
  }

  // check input for known XMLRPC attacks against this or other libs
  function payload_is_safe($input)
  {
      return true;
  }
?>