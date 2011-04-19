{**
  XSD template used to describe a 'choice of X, Y, Z' type in an xsd context
  @param $typename
  @param $tbasetypes
*}
{def $simpletype = true()}
{foreach $basetypes as $basetype}
    {if $basetype|begins_with('xsd:')|not()}
        {set $simpletype = false()}
        {break}
    {/if}
{/foreach}
{if $simpletype}
<xsd:simpleType name="{$typename}">
    <xsd:union memberTypes="{foreach $basetypes as $basetype}{$basetype}{delimiter} {/delimiter}{/foreach}"/>
</xsd:simpleType>
{else}
<xsd:complexType name="{$typename}">
    <xsd:choice>
	    {foreach $basetypes as $basetype}
	        <xsd:element name="TO BE DONE..." type="{$basetype}"/>

	    {/foreach}
    </xsd:choice>
</xsd:complexType>
{/if}
