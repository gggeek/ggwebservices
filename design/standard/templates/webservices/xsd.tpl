<?xml version='1.0' encoding='UTF-8'?>
{**
 * XSD template
 *
 * @todo ...
 *}
<xsd:schema
  xmlns:xsd="http://www.w3.org/2001/XMLSchema">
{def $types = array()
     $ptype = ''
     $rtype = ''}
{foreach $functions as $fname => $function}
    {foreach $function.params as $name => $type}
        {set $ptype = $type|xsdtype('tnsxsd:')}
        {if $ptype|begins_with('tnsxsd:')}
            {if $types|contains($ptype)|not()}
                {set $types = $types|append($ptype)}
            {/if}
        {/if}
    {/foreach}
    {set $rtype = $function.returntype|xsdtype('tnsxsd:')}
    {if $rtype|begins_with('tnsxsd:')}
        {if $types|contains($ptype)|not()}
            {set $types = $types|append($ptype)}
        {/if}
    {/if}
{/foreach}

{foreach $types as $type}
    {set $type = $type|extract(7)}
    {if $type|begins_with('arrayOf')}
        {include uri='design:webservices/xsd/array.tpl' typename=$type basetype=$type|extract(7)}
    {elseif $type|begins_with('choiceOf')}
        {include uri='design:webservices/xsd/array.tpl' typename=$type basetypes=$type|extract(8)|explode('Or')}
    {elseif $type|begins_with('class')}
        {include uri='design:webservices/xsd/class.tpl' typename=$type basetype=$type|extract(5)}
    {else}
        {* @todo ... *}
    {/if}

{/foreach}
</xsd:schema>