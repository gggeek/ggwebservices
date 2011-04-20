{if $inline|not()}<?xml version='1.0' encoding='UTF-8'?>
{**
 * XSD template
 *}
<xsd:schema
  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/">
{def $ptype = ''
     $rtype = ''
     $typename = ''
     $newtypes = array()}
{foreach $functions as $fname => $function}
    {foreach $function.params as $name => $type}
        {set $ptype = $type|xsdtype('tnsxsd:')}
        {if $ptype|begins_with('tnsxsd:')}
            {if $newtypes|contains($ptype)|not()}
                {set $newtypes = $newtypes|append($ptype)}
            {/if}
        {/if}
    {/foreach}
    {set $rtype = $function.returntype|xsdtype('tnsxsd:')}
    {if $rtype|begins_with('tnsxsd:')}
        {if $newtypes|contains($rtype)|not()}
            {set $newtypes = $newtypes|append($rtype)}
        {/if}
    {/if}
{/foreach}
{undef $rtype $ptype}

{/if}

{* every time we include a new class the array $newtypes might be augmented with new types *}
{def $min = 0 $max = -1 $limit = 0}
{while $max|lt($newtypes|count()|dec())}
    {set $limit = $limit|inc()
         $min = $max|inc()
         $max = $newtypes|count()|dec()}
    {for $min to $max as $i}
        {set $type = $newtypes[$i]
             $typename = $type|explode(':')|extract(1)|implode('_')}
        {if $type|begins_with('tnsxsd:arrayOf')}
            {include uri='design:webservices/xsd/array.tpl' typename=$typename basetype=$type|extract(14) soapencns='SOAP-ENC'}
        {elseif $type|begins_with('tnsxsd:choiceOf')}
            {include uri='design:webservices/xsd/choice.tpl' typename=$typename basetypes=$type|extract(15)|explode('Or')}
        {elseif $type|begins_with('tnsxsd:class')}
            {include uri='design:webservices/xsd/class.tpl' typename=$typename basetype=$type|extract(12)}
        {else}
            {* @todo ... *}
        {/if}
    {/for}
    {* safety limit *}
    {if gt($limit, 10)}
        {break}
    {/if}
{/while}
{undef $min $max $limit}

{if $inline|not()}</xsd:schema>{/if}