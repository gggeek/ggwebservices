{**
 * Dialog for visually editing trees of json/xmlrpc values
 * @version $Id$
 * @copyright (C) 2006-2012 G. Giunta
 * @author Gaetano Giunta
 *
 * @todo do not set to "null" new nodes
 * @todo improve display: do not show up/down arrows, 'angle line' for top level parameter
 * @todo improve display: update the number of parameters when adding a new one
 * @todo improve display: hide top-level node
 *}
<head>
<title>WS Debugger Visual Editor</title>
{def $preferred_packing = '-min'}
{if eq('enabled', ezini('TemplateSettings', 'DevelopmentMode'))}
    {set $preferred_packing = ''}
{/if}
{def $preferred_version = ezini('DebuggerSettings', 'PreferredVersion', 'wsproviders.ini').yui2}
{* allow user to specify to go with ezjscore's version, whatever that might be *}
{if eq($preferred_version, 'ezjscore')}
    {set $preferred_version =  ezini('eZJSCore', 'LocalScriptBasePath', 'ezjscore.ini').yui2}
{else}
    {* try to survive misconfigurations at least a bit *}
    {if eq($preferred_version, '')}
        {set $preferred_version = '2.5.0'}
    {/if}
    {set $preferred_version = concat('lib/yui/', $preferred_version, '/build/')}
{/if}
<!-- YUI Treeview component: base libs -->
<script type="text/javascript" src={concat($preferred_version, 'yahoo/yahoo', $preferred_packing, '.js')|ezdesign()} ></script>
<script type="text/javascript" src={concat($preferred_version, 'event/event', $preferred_packing, '.js')|ezdesign()} ></script>
<!-- YUI Treeview component: treeview -->
<script type="text/javascript" src={concat($preferred_version, 'treeview/treeview', $preferred_packing, '.js')|ezdesign()} ></script>
<link rel="stylesheet" type="text/css" href={'stylesheets/debugger/tree.css'|ezdesign()} />
<!-- YUI Dialog component -->
<script type="text/javascript" src={concat($preferred_version, 'dom/dom', $preferred_packing, '.js')|ezdesign()} ></script>
<script type="text/javascript" src={concat($preferred_version, 'dragdrop/dragdrop', $preferred_packing, '.js')|ezdesign()} ></script>
<script type="text/javascript" src={concat($preferred_version, 'container/container', $preferred_packing, '.js')|ezdesign()} ></script>
<link rel="stylesheet" type="text/css" href={'stylesheets/debugger/container.css'|ezdesign()} />

<!-- xmlrpc/jsonrpc base library -->
<script type="text/javascript" src={'javascript/xmlrpc_lib.js'|ezdesign()}></script>
<script type="text/javascript" src={'javascript/jsonrpc_lib.js'|ezdesign()}></script>
<!-- display components -->
<script type="text/javascript" src={'javascript/xmlrpc_display.js'|ezdesign()}></script>
<link rel="stylesheet" type="text/css" href={'stylesheets/debugger/xmlrpc_tree.css'|ezdesign()} />
{undef $preferred_packing $preferred_version}

<script type="text/javascript">
<!--
{* set up xmlrpc lib options *}
askDeleteConfirmation = true;
editElementDiv = 'dlgpanel';
allowTopLevelElementTypeChange = false;
elementType = 'jsonrpcval';

var roottree = null;
var rootnode = null;
var source = {$paramsjson};

function treeInit()

{ldelim}

  roottree = new YAHOO.widget.TreeView('valuepanel');
  rootnode = new YAHOO.widget.XMLRPCNode(jsonrpc_encode(source), roottree.getRoot(), true, null, true);
  roottree.draw()
  document.getElementById('numparams').innerHTML = '{$paramscount}';

{rdelim}

function buildthem()

{ldelim}

  var root = roottree.getRoot().children[0];
  root.toggleEditable();
  roottree.draw();
  return root.data.serialize();

{rdelim}

{literal}
function done()
{
  out = base64_encode(buildthem());
  if (window.opener && window.opener.buildparams)
  {
    window.opener.buildparams(out);
  }
  window.close();
}
{/literal}//-->
</script>

<link rel="stylesheet" type="text/css" href={'stylesheets/debugger/visualeditor.css'|ezdesign()} />

</head>
<body onload="treeInit();">
<h2>Editing <span id="numparams"></span>&nbsp; parameters</h2>
<h3>
{*{if $noadd|not()}*}
<a href="#" onclick="roottree.getRoot().children[0].onAddClick(roottree.getRoot().children[0]); return false;">Add parameter</a> |
{*{/if}*}
<a href="#" onclick="treeInit(); return false;">Reset all</a> |
<a href="#" onclick="window.close();">Cancel</a> |
<a href="#" onclick="done(); return false;">Submit</a>
</h3>
<noscript>
WARNING: this page is completely useless without javascript support.<br />Please use a javascript-enabled browser
</noscript>
<div id="dlgpanel"></div>
<div id="valuepanel"></div>
<!--DEBUG_REPORT-->
</body>
