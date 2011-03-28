{**
 * Dialog for visually editing trees of json/xmlrpc values
 * @version $Id$
 * @copyright (C) 2006-2010 G. Giunta
 * @author Gaetano Giunta
 *
 * @todo support nested structs / arrays when opening
 * @todo support soap convention (top level can be a struct, not an array)
 *
 * @todo do not set to "null" new nodes
 * @todo improve display: do not show up/down arrows, 'angle line' for parameters, move up list numbers in ff
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
/* set up xmlrpc lib display options */
askDeleteConfirmation = true;
editElementDiv = 'dlgpanel';

{if $noadd|not()}allowTopLevelElementTypeChange = true;{/if}

elementType = '{$type}val';

var trees = [];
var nodes = [];

function treeInit()

{ldelim}

  trees = [];
  nodes = [];

{def $divs = ''
     $trees = ''
     $ptype = ''
     $pval = ''}
{foreach $params as $i => $param}
	{set $ptype = $param.type|downcase()
	     $pval = $param.value
         $trees = $trees|append("  trees[",$i,"] = new YAHOO.widget.TreeView('param",$i,"');\n")}
    {if eq($ptype, 'struct')}
        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val({\}, '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
    {elseif eq($ptype, 'array')}
        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val([], '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
    {elseif $valid_types|contains($ptype)}
	    {if eq($ptype, 'datetime.iso8601')} {* we need a mixed-case type specifier for dates *}
	        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val(null, 'dateTime.iso8601'), trees[",$i,"].getRoot(), true, null, true);\n")}
	    {elseif eq($ptype, 'string')}
	        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val('",$pval|wash(javascript),"', '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
	    {else}
            {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val(",$pval|wash(javascript),", '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
        {/if}
    {else}
        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val(), trees[",$i,"].getRoot(), true, null, true);\n")}
    {/if}
    {set $trees = $trees|append("  trees[",$i,"].draw()\n")}
    {*echo "<h3>Parameter $i: $ptype</h3>\n";*}
    {set $divs = $divs|append("<li id=\"param",$i,"\" class=\"paramdiv\"></li>\\n")}
{/foreach}
  document.getElementById('valuepanel').innerHTML = '{$divs}';
{$trees}
  document.getElementById('numparams').innerHTML = '{$params|count()}';

{rdelim}

{literal}
function addParam()
{
  showEditDlg(false, null, true, false, function(name, type, value) {

    // add a div for the tree to the document
    // add a tree
    var next = trees.length;
    var newTree = document.createElement("li");
    newTree.className = 'paramdiv';
    document.getElementById('valuepanel').appendChild(newTree);
    trees[next] = new YAHOO.widget.TreeView(newTree);
    nodes[next] = new YAHOO.widget.XMLRPCNode(buildVal(type, value), trees[next].getRoot(), true, null, true);
    trees[next].draw();
    document.getElementById('numparams').innerHTML = (next+1);

  });
}
{/literal}
function buildthem()

{ldelim}

  var out = '[ ';
  var root;
  for (var i = 0; i < trees.length; i++)
  {ldelim}

    root = trees[i].getRoot().children[0];
    root.toggleEditable();
    trees[i].draw();
{*if eq($type,'jsonrpc')*}
    /// @todo use an array and implode() here? it wouldbe cleaner...
    out += root.data.serialize()+', ';
  {rdelim}

  out = out.slice(0, -2)+' ]';
{*else}

    out += '<param>\n'+root.data.serialize()+'</param>\n';
  {rdelim}

{/if*}

  return out;

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
{if $noadd|not()}
<a href="#" onclick="addParam(); return false;">Add parameter</a> |
{/if}
<a href="#" onclick="treeInit(); return false;">Reset all</a> |
<a href="#" onclick="window.close();">Cancel</a> |
<a href="#" onclick="done(); return false;">Submit</a>
</h3>
<noscript>
WARNING: this page is completely useless without javascript support.<br />Please use a javascript-enabled browser
</noscript>
<div id="dlgpanel"></div>
<ol id="valuepanel"></ol>
<!--DEBUG_REPORT-->
</body>
