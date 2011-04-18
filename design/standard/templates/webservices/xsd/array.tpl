{**
  XSD template used to describe an 'array of X' complex type in a soap context
  @param $typename
  @param $basetype
*}
<xsd:complexType name="{$typename}">
    <xsd:complexContent>
	    <xsd:restriction base="SOAP-ENC:Array">
		    <xsd:sequence>
			    <xsd:element name="item" type="{$basetype}" maxOccurs="unbounded"/>
            </xsd:sequence>
            <xsd:attribute ref="SOAP-ENC:arrayType" wsdl:arrayType="{$basetype}[]"/>
        </xsd:restriction>
    </xsd:complexContent>
</xsd:complexType>