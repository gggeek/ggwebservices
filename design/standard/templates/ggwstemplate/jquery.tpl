{**
 * Template that includes the javascript code exposing ws functionality to js.
 * We do it 'the ezjsc way', and also 'the plain way', thus including some
 * non-template javascript here.
 * NB: if the files included here change, the cache should probably have to be
 * cleaned by hand, as this template does not sense it... (to be verified)
 *
 * @author G. Giunta
 * @version $Id$
 * @copyright (C) G. Giunta 2009
 *
 * @todo add a check to see if param $arguments.0 is set and a valid protocol...
 * @todo test again in vhost mode
 *}
{def $path=''}
{if eq($arguments.0, 'jsonrpc')}
    {set $path='javascript/jquery/jquery.json-2.2.js'|ezdesign('no')}
    {if $path|begins_with(ezroot('no'))}
        {set $path=$path|extract(ezroot('no')|count_chars())}
    {else}
        {set $path=$path|extract(1)}
    {/if}
    {include uri=concat('file:', $path)}
{/if}
{set $path=concat('javascript/jquery/', $arguments.0, '.js')|ezdesign('no')}
{if $path|begins_with(ezroot('no'))}
    {set $path=$path|extract(ezroot('no')|count_chars())}
{else}
    {set $path=$path|extract(1)}
{/if}
{include uri=concat('file:', $path)}
{undef $path}
