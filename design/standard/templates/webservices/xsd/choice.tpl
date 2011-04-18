{**
  XSD template used to describe a 'choice of X, Y, Z' complex type in a soap context
  @param $typename
  @param $tbasetypes
*}
<xsd:complexType name="{$typename}">
    <xsd:choice>
	    {foreach $basetypes as $basetype}
	        <xsd:element name="..." type="{$basetype}"/>
	    {/foreach}
    </xsd:choice>
</xsd:complexType>