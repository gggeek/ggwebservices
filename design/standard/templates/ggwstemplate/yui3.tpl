{**
 * Template that generates the javascript code exposing ws functionality to YUI
 * (i.e. the Y.ez.wsproxy and Y.ez.jsonrpc methods).
 * We allow users to include that 'the plain way' and also 'the ezjscore way', hence
 * why the need for this template - which is no more than a wrapper.
 * NB: if the files included here change, the cache should probably have to be
 * cleaned by hand, as this template does not sense it... (to be verified)
 *
 * @author G. Giunta
 * @copyright (C) 2009-2013 G. Giunta
 * @license code licensed under the GPL License: see LICENSE file
 *
 * @todo add a check to see if param $arguments.0 is set and a valid protocol...
 * @todo test again in vhost mode
 *}
{def $js_path=concat('javascript/yui/3.0/build/io/', $arguments.0, '.js')|ezdesign('no')}
{if $js_path|begins_with(ezroot('no'))}
    {set $js_path=$js_path|extract(ezroot('no')|count_chars())}
{else}
    {set $js_path=$js_path|extract(1)}
{/if}
{include uri=concat('file:', $js_path)}
{undef $js_path}