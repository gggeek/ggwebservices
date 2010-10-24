{**
 * @version $Id: controller.php 115 2009-10-02 22:38:16Z gg $
 * @author Gaetano Giunta
 * @copyright (C) 2005-2008 G. Giunta
 * @license code licensed under the BSD License: http://phpxmlrpc.sourceforge.net/license.txt
 *
 * @todo add links to documentation from every option caption
 * @todo switch params for http compression from 0,1,2 to values to be used directly
 * @todo add a little bit more CSS formatting: we broke IE box model getting a width > 100%...
 * @todo add support for more options, such as ntlm auth to proxy, or request charset encoding
 *
 * @todo parse content of payload textarea to be fed to visual editor
 *
 * @todo move inline css, js to external files
 *}
<head>
<title>XMLRPC Debugger</title>
<meta name="robots" content="index,nofollow" />
<!-- xmlrpc/jsonrpc base library -->
<script type="text/javascript" src={'javascript/xmlrpc_lib.js'|ezdesign()}></script>
<script type="text/javascript" src={'javascript/jsonrpc_lib.js'|ezdesign()}></script>
<style type="text/css">
<!--{literal}
html {overflow: -moz-scrollbars-vertical;}
body {padding: 0.5em; background-color: #EEEEEE; font-family: Verdana, Arial, Helvetica; font-size: 8pt;}
h1 {font-size: 12pt; margin: 0.5em;}
h2 {font-size: 10pt; display: inline; vertical-align: top;}
table {border: 1px solid gray; margin-bottom: 0.5em; padding: 0.25em; width: 100%;}
#methodpayload {display: inline;}
td {vertical-align: top; font-family: Verdana, Arial, Helvetica; font-size: 8pt;}
.labelcell {text-align: right;}
{/literal}-->
</style>
<script language="JavaScript" type="text/javascript">
<!--{literal}
  function verifyserver()
  {
    if (document.frmaction.host.value == '')
    {
      alert('Please insert a server name or address');
      return false;
    }
    if (document.frmaction.path.value == '')
      document.frmaction.path.value = '/';
    var action = '';
    for (counter = 0; counter < document.frmaction.action.length; counter++)
      if (document.frmaction.action[counter].checked)
      {
        action = document.frmaction.action[counter].value;
      }
    if (document.frmaction.method.value == '' && (action == 'execute' || action == 'wrap' || action == 'describe'))
    {
      alert('Please insert a method name');
      return false;
    }
    if (document.frmaction.authtype.value != '1' && document.frmaction.username.value == '')
    {
      alert('No username for authenticating to server: authentication disabled');
    }
    return true;
  }

  function switchaction()
  {
    // reset html layout depending on action to be taken
    var action = '';
    for (counter = 0; counter < document.frmaction.action.length; counter++)
      if (document.frmaction.action[counter].checked)
      {
        action = document.frmaction.action[counter].value;
      }
    if (action == 'execute')
    {
      document.frmaction.methodpayload.disabled = false;
      displaydialogeditorbtn(true);//if (document.getElementById('methodpayloadbtn') != undefined) document.getElementById('methodpayloadbtn').disabled = false;
      document.frmaction.method.disabled = false;
      document.frmaction.methodpayload.rows = 10;
    }
    else
    {
      document.frmaction.methodpayload.rows = 1;
      if (action == 'describe' || action == 'wrap')
      {
        document.frmaction.methodpayload.disabled = true;
        displaydialogeditorbtn(false); //if (document.getElementById('methodpayloadbtn') != undefined) document.getElementById('methodpayloadbtn').disabled = true;
        document.frmaction.method.disabled = false;
      }
      else // list
      {
        document.frmaction.methodpayload.disabled = true;
        displaydialogeditorbtn(false); //if (document.getElementById('methodpayloadbtn') != undefined) document.getElementById('methodpayloadbtn').disabled = false;
        document.frmaction.method.disabled = true;
      }
    }
  }

  function switchssl()
  {
    if (document.frmaction.protocol.value != '2')
    {
      document.frmaction.verifypeer.disabled = true;
      document.frmaction.verifyhost.disabled = true;
      document.frmaction.cainfo.disabled = true;
    }
    else
    {
      document.frmaction.verifypeer.disabled = false;
      document.frmaction.verifyhost.disabled = false;
      document.frmaction.cainfo.disabled = false;
    }
  }

  function switchauth()
  {
    if (document.frmaction.protocol.value != '0')
    {
      document.frmaction.authtype.disabled = false;
    }
    else
    {
      document.frmaction.authtype.disabled = true;
      document.frmaction.authtype.value = 1;
    }
  }

  function swicthcainfo()
  {
    if (document.frmaction.verifypeer.checked == true)
    {
      document.frmaction.cainfo.disabled = false;
    }
    else
    {
      document.frmaction.cainfo.disabled = true;
    }
  }

  function switchtransport(is_json)
  {
    if (is_json == 0)
    {
      document.getElementById("idcell").style.visibility = 'hidden';
      document.frmjsonrpc.yes.checked = false;
      document.frmxmlrpc.yes.checked = true;
      document.frmaction.wstype.value="0";
    }
    else
    {
      document.getElementById("idcell").style.visibility = 'visible';
      document.frmjsonrpc.yes.checked = true;
      document.frmxmlrpc.yes.checked = false;
      document.frmaction.wstype.value="1";
    }
  }

  function displaydialogeditorbtn(show)
  {
    if (show && ((typeof base64_decode) == 'function'))
    {
	  document.getElementById('methodpayloadbtn').innerHTML = '[<a href="#" onclick="activateeditor(); return false;">Edit</a>]';
	}
	else
    {
	  document.getElementById('methodpayloadbtn').innerHTML = '';
	}
  }

  function activateeditor()
  {
	  var url = '{/literal}{concat('webservices/debugger/visualeditor?params=',$alt_payload)|ezurl(no)}{literal}';
	  if (document.frmaction.wstype.value == "1")
	    url += '&type=jsonrpc';
	  var wnd = window.open(url, '_blank', 'width=750, height=400, location=0, resizable=1, menubar=0, scrollbars=1');
  }

  // if javascript version of the lib is found, allow it to send us params
  function buildparams(base64data)
  {
    if (typeof base64_decode == 'function')
    {
	  if (base64data == '0') // workaround for bug in base64_encode...
	    document.getElementById('methodpayload').value = '';
	  else
        document.getElementById('methodpayload').value = base64_decode(base64data);
    }
  }

  // use GET for ease of refresh, switch to POST when payload is too big to fit in url (in IE: 2048 bytes! see http://support.microsoft.com/kb/q208427/)
  function switchFormMethod()
  {
      /// @todo use a more precise calculation, adding the rest of the fields to the actual generated url lenght
      if (document.frmaction.methodpayload.value.length > 1536 )
      {
          document.frmaction.action = document.frmaction.action + '/?usepost=true';
          document.frmaction.method = 'post';
      }
  }

{/literal}//-->
</script>
</head>
<body onload="switchtransport({$wstype}); switchaction(); switchssl(); switchauth(); swicthcainfo();{if $run} document.forms[2].submit();{/if}">
<h1>XMLRPC <form name="frmxmlrpc" style="display: inline;" action="."><input name="yes" type="radio" onclick="switchtransport(0);"/></form>
/<form name="frmjsonrpc" style="display: inline;" action="."><input name="yes" type="radio" onclick="switchtransport(1);"/></form>JSONRPC Debugger (based on the <a href="http://phpxmlrpc.sourceforge.net">PHP-XMLRPC</a> library)</h1>
<form name="frmaction" method="get" action="../action/" target="frmaction" onSubmit="switchFormMethod();"
>

<table id="serverblock">
<tr>
<td><h2>Target server</h2></td>
<td class="labelcell">Address:</td><td><input type="text" name="host" value="{$host|wash()}" /></td>
<td class="labelcell">Port:</td><td><input type="text" name="port" value="{$port|wash()}" size="5" maxlength="5" /></td>
<td class="labelcell">Path:</td><td><input type="text" name="path" value="{$path|wash()}" /></td>
</tr>
</table>

<table id="actionblock">
<tr>
<td><h2>Action</h2></td>
<td>List available methods<input type="radio" name="action" value="list"{if eq($action,'list')} checked="checked"{/if} onclick="switchaction();" /></td>
<td>Describe method<input type="radio" name="action" value="describe"{if eq($action, 'describe')} checked="checked"{/if} onclick="switchaction();" /></td>
<td>Execute method<input type="radio" name="action" value="execute"{if eq($action, 'execute')} checked="checked"{/if} onclick="switchaction();" /></td>
<!--<td>Generate stub for method call<input type="radio" name="action" value="wrap"{if eq($action, 'wrap')} checked="checked"{/if} onclick="switchaction();" /></td>-->
</tr>
</table>
<input type="hidden" name="methodsig" value="{$methodsig|wash()}" />

<table id="methodblock">
<tr>
<td><h2>Method</h2></td>
<td class="labelcell">Name:</td><td><input type="text" name="method" value="{$method|wash()}" /></td>
<td class="labelcell">Payload:<br/><div id="methodpayloadbtn"></div></td><td><textarea id="methodpayload" name="methodpayload" rows="1" cols="40">{$payload|wash()}</textarea></td>
<td class="labelcell" id="idcell">Msg id: <input type="text" name="id" size="3" value="{$id|wash()}"/></td>
<td><input type="hidden" name="wstype" value="{$wstype}" />
<input type="submit" value="Execute" onclick="return verifyserver();"/></td>
</tr>
</table>

<table id="optionsblock">
<tr>
<td><h2>Client options</h2></td>
<td class="labelcell">Show debug info:</td><td><select name="debug">
<option value="0"{if eq($debug, 0)} selected="selected"{/if}>No</option>
<option value="1"{if eq($debug, 1)} selected="selected"{/if}>Yes</option>
<option value="2"{if eq($debug, 2)} selected="selected"{/if}>More</option>
</select>
</td>
<td class="labelcell">Timeout:</td><td><input type="text" name="timeout" size="3" value="{if gt($timeout, 0)}$timeout{/if}" /></td>
<td class="labelcell">Protocol:</td><td><select name="protocol" onchange="switchssl(); switchauth(); swicthcainfo();">
<option value="0"{if eq($protocol, 0)} selected="selected"{/if}>HTTP 1.0</option>
<option value="1"{if eq($protocol, 1)} selected="selected"{/if}>HTTP 1.1</option>
<option value="2"{if eq($protocol, 2)} selected="selected"{/if}>HTTPS</option>
</select></td>
</tr>
<tr>
<td class="labelcell">AUTH:</td>
<td class="labelcell">Username:</td><td><input type="text" name="username" value="{$username|wash()}" /></td>
<td class="labelcell">Pwd:</td><td><input type="password" name="password" value="{$password|wash()}" /></td>
<td class="labelcell">Type</td><td><select name="authtype">
<option value="1"{if eq($authtype, 1)} selected="selected"{/if}>Basic</option>
<option value="2"{if eq($authtype, 2)} selected="selected"{/if}>Digest</option>
<option value="8"{if eq($authtype, 8)} selected="selected"{/if}>NTLM</option>
</select></td>
<td></td>
</tr>
<tr>
<td class="labelcell">SSL:</td>
<td class="labelcell">Verify Host's CN:</td><td><select name="verifyhost">
<option value="0"{if eq($verifyhost, 0)} selected="selected"{/if}>No</option>
<option value="1"{if eq($verifyhost, 1)} selected="selected"{/if}>Check CN existance</option>
<option value="2"{if eq($verifyhost, 2)} selected="selected"{/if}>Check CN match</option>
</select></td>
<td class="labelcell">Verify Cert:</td><td><input type="checkbox" value="1" name="verifypeer" onclick="swicthcainfo();"{if $verifypeer} checked="checked"{/if} /></td>
<td class="labelcell">CA Cert file:</td><td><input type="text" name="cainfo" value="{$cainfo|wash()}" /></td>
</tr>
<tr>
<td class="labelcell">PROXY:</td>
<td class="labelcell">Server:</td><td><input type="text" name="proxy" value="{$proxy|wash()}" /></td>
<td class="labelcell">Proxy user:</td><td><input type="text" name="proxyuser" value="{$proxyuser|wash()}" /></td>
<td class="labelcell">Proxy pwd:</td><td><input type="password" name="proxypwd" value="{$proxypwd|wash()}" /></td>
</tr>
<tr>
<td class="labelcell">COMPRESSION:</td>
<td class="labelcell">Request:</td><td><select name="requestcompression">
<option value="0"{if eq($requestcompression, 0)} selected="selected"{/if}>None</option>
<option value="1"{if eq($requestcompression, 1)} selected="selected"{/if}>Gzip</option>
<option value="2"{if eq($requestcompression, 2)} selected="selected"{/if}>Deflate</option>
</select></td>
<td class="labelcell">Response:</td><td><select name="responsecompression">
<option value="0"{if eq($responsecompression, 0)} selected="selected"{/if}>None</option>
<option value="1"{if eq($responsecompression, 1)} selected="selected"{/if}>Gzip</option>
<option value="2"{if eq($responsecompression, 2)} selected="selected"{/if}>Deflate</option>
<option value="3"{if eq($responsecompression, 3)} selected="selected"{/if}>Any</option>
</select></td>
<td></td>
</tr>
<tr>
<td class="labelcell">COOKIES:</td>
<td colspan="4" class="labelcell"><input type="text" name="clientcookies" size="80" value="{$clientcookies|wash()}" /></td>
<td colspan="2">Format: 'cookie1=value1, cookie2=value2'</td>
</tr>
</table>

</form>
</body>