{**
  XSD template used to describe a 'php class' complex type in a soap context
  @param string $typename
  @param string $basetype
  @param array $newtypes
*}
{def $proptypename=''}
<xsd:complexType name="{$typename}">
    <xsd:sequence>
    {foreach $basetype|classInspect() as $property => $type}
        {set $proptypename = $type|xsdtype('tnsxsd:')}
        {if $proptypename|begins_with('tnsxsd:')}
            {if $newtypes|contains($proptypename)|not()}
                {set $newtypes = $newtypes|append($proptypename)}
            {/if}
            {set $proptypename = 'tnsxsd:'|append($proptypename|explode(':')|extract(1)|implode('_'))}
        {/if}
        <xsd:element name="{$property}" type="{$proptypename}"/>
    {/foreach}
    </xsd:sequence>
</xsd:complexType>
