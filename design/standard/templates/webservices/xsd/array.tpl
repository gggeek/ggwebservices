{**
  XSD template used to describe an 'array of X' complex type in a soap context
  @param $typename
  @param $basetype
  @param $soapencns
  @param array $newtypes
*}
{if $basetype|begins_with('tnsxsd:')}
    {if $newtypes|contains($basetype)|not()}
        {set $newtypes = $newtypes|append($basetype)}
    {/if}
    {*set $proptypename = 'tnsxsd:'|append($proptypename|explode(':')|extract(1)|implode('_'))*}
{/if}
<xsd:complexType name="{$typename}">
    <xsd:complexContent>
	    <xsd:restriction base="{$soapencns}:Array">
		    <xsd:sequence>
			    <xsd:element name="item" type="{$basetype}" maxOccurs="unbounded"/>
            </xsd:sequence>
            <xsd:attribute ref="{$soapencns}:arrayType" wsdl:arrayType="{$basetype}[]"/>
        </xsd:restriction>
    </xsd:complexContent>
</xsd:complexType>
