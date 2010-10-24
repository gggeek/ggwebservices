{**
 * Dialog for visually editing trees of json/xmlrpc values
 * @version $Id: visualeditor.php 161 2010-10-23 22:36:17Z gg $
 * @copyright G. Giunta 2006-2010
 * @author Gaetano Giunta
 *
 * @todo do not set to "null" new nodes
 * @todo find a better way to preview large trees of values (at least make all panel draggable)
 * @todo improve display: do not show up/down arrows, 'angle line' for parameters, move up list numbers in ff
 *}
<head>
<title>XMLRPC Debugger Visual Editor</title>

<!-- YUI Treeview component: base libs -->
<script type="text/javascript" src={'lib/yui/2.5.0/build/yahoo/yahoo.js'|ezdesign()} ></script>
<script type="text/javascript" src={'lib/yui/2.5.0/build/event/event.js'|ezdesign()} ></script>
<!-- YUI Treeview component: treeview -->
<script type="text/javascript" src={'lib/yui/2.5.0/build/treeview/treeview.js'|ezdesign()} ></script>
<link rel="stylesheet" type="text/css" href={'debugger/tree.css'|ezdesign()} />
<!-- YUI Dialog component -->
<script type="text/javascript" src={'lib/yui/2.5.0/build/dom/dom.js'|ezdesign()} ></script>
<script type="text/javascript" src={'lib/yui/2.5.0/build/dragdrop/dragdrop.js'|ezdesign()} ></script>
<script type="text/javascript" src={'lib/yui/2.5.0/build/container/container.js'|ezdesign()} ></script>
<link rel="stylesheet" type="text/css" href={'/debugger/container.css'|ezdesign()} />

<!-- xmlrpc/jsonrpc base library -->
<script type="text/javascript" src={'javascript/xmlrpc_lib.js'|ezdesign()}></script>
<script type="text/javascript" src={'javascript/jsonrpc_lib.js'|ezdesign()}></script>
<!-- display components -->
<script type="text/javascript" src={'javascript/xmlrpc_display.js'|ezdesign()}></script>
<link rel="stylesheet" type="text/css" href={'debugger/xmlrpc_tree.css'|ezdesign()} />

<script type="text/javascript">
<!--
/* set up xmlrpc lib display options */
askDeleteConfirmation = true;
editElementDiv = 'dlgpanel';

{if $noadd|not()}allowTopLevelElementTypeChange = true;{/if}

elementType = '{$type}val';

var trees = [];
var nodes = [];
var previewDlg = null;

function treeInit()

{ldelim}

  trees = [];
  nodes = [];
{def $divs = ''
     $trees = ''}
{foreach $params as $i => $ptype}
	{set $ptype = $ptype|downcase()}
    {set $trees = $trees|append("  trees[",$i,"] = new YAHOO.widget.TreeView('param",$i,"');\n")}
    {if eq($ptype, 'struct')}
        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val({\}, '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
    {elseif eq($ptype, 'array')}
        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val([], '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
    {elseif $valid_types|contains($ptype)}
	    {if eq($ptype, 'datetime.iso8601')} {* we need a mixed-case type specifier for dates *}
	        {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val(null, 'dateTime.iso8601'), trees[",$i,"].getRoot(), true, null, true);\n")}
	    {else}
            {set $trees = $trees|append("  nodes[",$i,"] = new YAHOO.widget.XMLRPCNode(new ",$type,"val(null, '",$ptype,"'), trees[",$i,"].getRoot(), true, null, true);\n")}
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

  var out = '';
  var root;
  for (var i = 0; i < trees.length; i++)
  {ldelim}

    root = trees[i].getRoot().children[0];
    root.toggleEditable();
    trees[i].draw();
{if eq($type,'jsonrpc')}
    /// @todo use an array and implode() here? it wouldbe cleaner...
    out += root.data.serialize()+',\\n';
  {rdelim}

  out = out.slice(0, -2)+'\\n';
{else}

    out += '<param>\n'+root.data.serialize()+'</param>\n';
  {rdelim}

{/if}

  return out;

{rdelim}

{literal}
function hidePreviewDlg()
{
  for (var i = 0; i < trees.length; i++)
  {
    root = trees[i].getRoot().children[0];
    root.toggleEditable();
    trees[i].draw();
  }
  this.hide();
}

function preview()
{
  if (nodes.length == 0)
    alert('No parameters to be serialized');
  else
  {
    //alert(buildthem());
    document.getElementById(editElementDiv).innerHTML = '<div class="hd">Serialized parameters</div>'+
      '<div class="bd"><pre>' + htmlentities(buildthem()) + '</pre></div>';
    previewDlg = new YAHOO.widget.Dialog(editElementDiv, {
      width : "400px",
      x: 240,
      y: 75,
      fixedcenter : false,
      visible : true,
      modal: true,
      draggable: true,
      constraintoviewport : false,
      buttons : [ { text:"OK", handler:hidePreviewDlg, isDefault:true } ]
      //            { text:"Cancel", handler:editElementCancel } ]
    } );
    var kl1 = new YAHOO.util.KeyListener(document, { keys:27 },
      { fn:hidePreviewDlg,
        scope:previewDlg,
        correctScope:true }, "keyup" );
        // keyup is used here because Safari won't recognize the ESC
        // keydown event, which would normally be used by default
    previewDlg.cfg.queueProperty("keylisteners", [kl1]);
    previewDlg.render();
    previewDlg.show();
  }
}

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

<link rel="stylesheet" type="text/css" href={'debugger/visualeditor.css'|ezdesign()} />

</head>
<body onload="treeInit();">
<h2>Editing <span id="numparams"></span>&nbsp;{$type} parameters</h2>
<h3>
{if $noadd|not()}
<a href="#" onclick="addParam(); return false;">Add parameter</a> |
{/if}
<a href="#" onclick="treeInit(); return false;">Reset all</a> |
<a href="#" onclick="preview(); return false;">Preview</a> |
<a href="#" onclick="window.close();">Cancel</a> |
<a href="#" onclick="done(); return false;">Submit</a>
</h3>
<noscript>
WARNING: this page is completely useless without javascript support.<br />Please use a javascript-enabled browser
</noscript>
<div id="dlgpanel"></div>
<ol id="valuepanel"></ol>
</body>
