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
    for (counter = 0; counter < document.frmaction.wsaction.length; counter++)
        if (document.frmaction.wsaction[counter].checked)
        {
            action = document.frmaction.wsaction[counter].value;
        }
    if (document.frmaction.wsmethod.value == '' && (action == 'execute' || action == 'wrap' || action == 'describe'))
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
    for (counter = 0; counter < document.frmaction.wsaction.length; counter++)
        if (document.frmaction.wsaction[counter].checked)
        {
            action = document.frmaction.wsaction[counter].value;
        }
    if (action == 'execute')
    {
        document.frmaction.methodpayload.disabled = false;
        displaydialogeditorbtn(true); //document.frmezjscore.yes.checked == false);//if (document.getElementById('methodpayloadbtn') != undefined) document.getElementById('methodpayloadbtn').disabled = false;
        document.frmaction.wsmethod.disabled = false;
        document.frmaction.methodpayload.rows = 10;
    }
    else
    {
        document.frmaction.methodpayload.rows = 1;
        if (action == 'describe' || action == 'wrap')
        {
            document.frmaction.methodpayload.disabled = true;
            displaydialogeditorbtn(false); //if (document.getElementById('methodpayloadbtn') != undefined) document.getElementById('methodpayloadbtn').disabled = true;
            document.frmaction.wsmethod.disabled = false;
            document.frmaction.wsdl.checked = true;
        }
        else // list || inspect
        {
            document.frmaction.methodpayload.disabled = true;
            displaydialogeditorbtn(false); //if (document.getElementById('methodpayloadbtn') != undefined) document.getElementById('methodpayloadbtn').disabled = false;
            document.frmaction.wsmethod.disabled = true;
            document.frmaction.wsdl.checked = true;
        }
    }
}

function switchssl()
{
    if (document.frmaction.protocol.value == '2' || (document.frmsoap.yes.checked == true && document.frmaction.wsdl.checked && true))
    {
        document.frmaction.verifypeer.disabled = false;
        document.frmaction.verifyhost.disabled = false;
        document.frmaction.cainfo.disabled = false;
    }
    else
    {
        document.frmaction.verifypeer.disabled = true;
        document.frmaction.verifyhost.disabled = true;
        document.frmaction.cainfo.disabled = true;
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

function switchtransport(wstype)
{
    if (wstype == 0)
    {
        document.getElementById("idcell").style.visibility = 'hidden';
        document.getElementById("restcell").style.visibility = 'hidden';
        document.frmjsonrpc.yes.checked = false;
        document.frmxmlrpc.yes.checked = true;
        document.frmezjscore.yes.checked = false;
        document.frmsoap.yes.checked = false;
        document.frmrest.yes.checked = false;
        document.frmaction.wstype.value="0";
        document.frmaction.listmethods.disabled = false;
        document.frmaction.describemethod.disabled = false;
        document.frmaction.executemethod.disabled = false;
        if (document.frmaction.inspectwsdl.checked == true)
        {
            document.frmaction.listmethods.checked = true
        }
        document.frmaction.inspectwsdl.disabled = true;
        document.frmaction.wsdl.disabled = true;
        document.frmaction.soapversion.disabled = true;
        document.frmaction.accept.disabled = true;
    }
    else if (wstype == 1)
    {
        document.getElementById("idcell").style.visibility = 'visible';
        document.getElementById("restcell").style.visibility = 'hidden';
        document.frmjsonrpc.yes.checked = true;
        document.frmxmlrpc.yes.checked = false;
        document.frmezjscore.yes.checked = false;
        document.frmsoap.yes.checked = false;
        document.frmrest.yes.checked = false;
        document.frmaction.wstype.value="1";
        document.frmaction.listmethods.disabled = false;
        document.frmaction.describemethod.disabled = false;
        document.frmaction.executemethod.disabled = false;
        if (document.frmaction.inspectwsdl.checked == true)
        {
            document.frmaction.listmethods.checked = true
        }
        document.frmaction.inspectwsdl.disabled = true;
        document.frmaction.wsdl.disabled = true;
        document.frmaction.soapversion.disabled = true;
        document.frmaction.accept.disabled = true;
    }
    else if (wstype == 2)
    {
        document.getElementById("idcell").style.visibility = 'hidden';
        document.getElementById("restcell").style.visibility = 'hidden';
        document.frmjsonrpc.yes.checked = false;
        document.frmxmlrpc.yes.checked = false;
        document.frmezjscore.yes.checked = true;
        document.frmsoap.yes.checked = false;
        document.frmrest.yes.checked = false;
        document.frmaction.wstype.value="2";
        document.frmaction.listmethods.disabled = false;
        document.frmaction.describemethod.disabled = false;
        document.frmaction.executemethod.disabled = false;
        if (document.frmaction.inspectwsdl.checked == true)
        {
            document.frmaction.listmethods.checked = true
        }
        document.frmaction.inspectwsdl.disabled = true;
        document.frmaction.wsdl.disabled = true;
        document.frmaction.soapversion.disabled = true;
        document.frmaction.accept.disabled = true;
    }
    else if (wstype == 3)
    {
        document.getElementById("idcell").style.visibility = 'hidden';
        document.getElementById("restcell").style.visibility = 'hidden';
        document.frmjsonrpc.yes.checked = false;
        document.frmxmlrpc.yes.checked = false;
        document.frmezjscore.yes.checked = false;
        document.frmsoap.yes.checked = true;
        document.frmrest.yes.checked = false;
        document.frmaction.wstype.value="3";
        //document.frmaction.listmethods.checked = false;
        document.frmaction.listmethods.disabled = false;
        //document.frmaction.describemethod.checked = false;
        document.frmaction.describemethod.disabled = false;
        //document.frmaction.executemethod.checked = true;
        document.frmaction.executemethod.disabled = false;
        document.frmaction.wsdl.disabled = false;
        document.frmaction.inspectwsdl.disabled = false;
        document.frmaction.soapversion.disabled = false;
        document.frmaction.accept.disabled = true;
    }
    else if (wstype == 4)
    {
        document.getElementById("idcell").style.visibility = 'hidden';
        document.getElementById("restcell").style.visibility = 'visible';
        document.frmjsonrpc.yes.checked = false;
        document.frmxmlrpc.yes.checked = false;
        document.frmezjscore.yes.checked = false;
        document.frmsoap.yes.checked = false;
        document.frmrest.yes.checked = true;
        document.frmaction.wstype.value="4";
        document.frmaction.executemethod.checked = true;
        //document.frmaction.listmethods.checked = false;
        document.frmaction.listmethods.disabled = true;
        //document.frmaction.describemethod.checked = false;
        document.frmaction.describemethod.disabled = true;
        document.frmaction.executemethod.disabled = false;
        document.frmaction.wsdl.disabled = true;
        document.frmaction.inspectwsdl.disabled = true;
        document.frmaction.soapversion.disabled = true;
        document.frmaction.accept.disabled = false;
        switchmethod();
    }
    // used to make sure the 'edit' link to the visual editor gets reset properly
    switchaction();
}

function displaydialogeditorbtn(show)
{
    if (show && ((typeof base64_decode) == 'function'))
    {
        document.getElementById('methodpayloadbtn').innerHTML = '<input type="submit" onclick="activateeditor(); return false;" value="Edit" />';
    }
    else
    {
        document.getElementById('methodpayloadbtn').innerHTML = '';
    }
}

function activateeditor()
{
    var url = ezrooturl + '/webservices/debugger/visualeditor';
    url =  url + '?params=' + base64_encode( document.getElementById('methodpayload').value );
    if (document.frmaction.wstype.value == "1")
        url += '&type=jsonrpc';
    else if (document.frmaction.wstype.value == "2")
        url += '&type=ezjscore';
    else if (document.frmaction.wstype.value == "3")
        url += '&type=soap';
    else if (document.frmaction.wstype.value == "4")
        url += '&type=rest';
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
    if ( /*document.frmaction.methodpayload.value.length > 1536*/ true )
    {
        document.frmaction.action = document.frmaction.action.replace(/\/\?usepost=(true|false)/, '') + '/?usepost=true';
        document.frmaction.method = 'post';
        document.frmaction.requesttype.disabled = false;
    }
    else
    {
        document.frmaction.action = document.frmaction.action.replace(/\/\?usepost=(true|false)/, '') + '/?usepost=false';
        document.frmaction.method = 'get';
        document.frmaction.requesttype.disabled = true;
    }
    return true;
}

function switchmethod()
{
    if ( document.frmaction.verb.value == 'GET' || document.frmaction.verb.value == 'HEAD' || document.frmaction.verb.value == 'TRACE' )
    {
        document.frmaction.requesttype.disabled = true;
        //document.frmaction.requesttypeCombo.disabled = true;
        $("#combobox").combobox('disable');
    }
    else
    {
        document.frmaction.requesttype.disabled = false;
        //document.frmaction.requesttypeCombo.disabled = false;
        $("#combobox").combobox('enable');
    }
}
