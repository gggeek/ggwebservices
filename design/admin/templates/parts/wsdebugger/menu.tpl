{* Left-col menu for ws debugger
 *
 * @author G. Giunta
 * @copyright (C) 2009-2013 G. Giunta
 *}
{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h4>{'Local server'|i18n('extension/ggwebservices')}</h4>
{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<ul>
{foreach $server_list as $protocol => $serverparams}
    {if ne( $serverparams, '' )}
        <li><div><a href={concat( 'webservices/debugger/controller/', $serverparams )|ezurl} target="frmcontroller">{$protocol|upcase}</a></div></li>
    {else}
        <li><div><span class="disabled">{$protocol|upcase}</span>
    {/if}
{/foreach}
</ul>

{* DESIGN: Content END *}</div></div></div></div></div></div>

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
<h4>{'Remote servers'|i18n('extension/ggwebservices')}</h4>
{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

<ul>
{foreach $target_list as $targetname => $targetspec}
    {if or( eq( $targetspec.providerType, 'JSONRPC' ), eq( $targetspec.providerType, 'XMLRPC' ), eq( $targetspec.providerType, 'eZJSCore' ), eq( $targetspec.providerType, 'PhpSOAP' ), eq( $targetspec.providerType, 'REST' ) )}
        <li><div><a href={concat( 'webservices/debugger/controller/', $targetspec.urlparams )|ezurl} target="frmcontroller">{$targetname|wash}</a></div></li>
    {else}
        {if  eq( $targetspec.providerType, 'FAULT' )}
        <li><div><span class="disabled" style="color: red;">{$targetname|wash}</span>
        {else}
        <li><div><span class="disabled">{$targetname|wash}</span>
        {/if}
    {/if}
{/foreach}
</ul>

{* DESIGN: Content END *}</div></div></div></div></div></div>