<?xml version='1.0' encoding='UTF-8'?>
{**
 * WSDL 1 template
 *
 * @todo allow user to specify 'location', via eg. an ini value
 *}
<wsdl:definitions

  name="{$servicename|washxml()}"
  targetNamespace="{$namespace|washxml()}"
  xmlns:tns="{$namespace|washxml()}"

  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
  xmlns="http://schemas.xmlsoap.org/wsdl/">

<!-- imports -->

<!-- types -->

<!-- messages -->

{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

<wsdl:message name="{$fname}Request">
{foreach $function.params as $name => $type}

    <wsdl:part name="{$name}" type="{$type|xsdtype()|washxml()}"/>
{/foreach}

</wsdl:message>

<wsdl:message name="{$fname}Response">
    <wsdl:part name="{$fname}Return" type="{$function.returntype|xsdtype()|washxml()}"/>
</wsdl:message>

{/foreach}

<!-- port name and binding  -->

<wsdl:portType name="{$servicename|washxml()}PortType">
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

    <wsdl:operation name="{$fname}">
        <wsdl:documentation>{$function.documentation|washxml()}</wsdl:documentation>
        <wsdl:input message="tns:{$fname}Request"/>
        <wsdl:output message="tns:{$fname}Response"/>
    </wsdl:operation>
{/foreach}

</wsdl:portType>

<wsdl:binding name="{$servicename|washxml()}SOAPBinding" type="tns:{$servicename|washxml()}PortType">
    <soap:binding
      style="rpc"
      transport="http://schemas.xmlsoap.org/soap/http"/>
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

    <wsdl:operation name="{$fname}">
        <soap:operation soapAction="urn:{$fname}Action"/>
        <wsdl:input>
            <soap:body
              namespace="urn:{$fname}"
              use="encoded"
              encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
        </wsdl:input>
        <wsdl:output>
            <soap:body
              namespace="urn:{$fname}"
              use="encoded"
              encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
        </wsdl:output>
    </wsdl:operation>
{/foreach}
</wsdl:binding>

<!-- services -->
<wsdl:service name="{$servicename|washxml()}Service">
    <wsdl:port name="{$servicename|washxml()}Port" binding="tns:{$servicename|washxml()}SOAPBinding">
        <soap:address location="{'webservices/execute/phpsoap'|ezurl(no, full)}" />
    </wsdl:port>
</wsdl:service>

</wsdl:definitions>