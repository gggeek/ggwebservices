<?xml version='1.0' encoding='UTF-8'?>
{**
 * WSDL template
 *
 * @todo import or merge $extras
 * @todo add definition of types for complex types
 * @todo fix namespaces
 *}
<definitions

  name="{$servicename}"
  targetNamespace="{concat('webservices/wsdl/',$wsname|urlencode())|ezurl(no, full)}"
  xmlns:tns="{concat('webservices/wsdl/',$wsname|urlencode())|ezurl(no, full)}"

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

<message name="{$fname}Request">
{foreach $function.params as $name => $type}

    <part name="{$name}" type="{$type|xsdtype()|washxml()}"/>
{/foreach}

</message>

<message name="{$fname}Response">
    <part name="{$fname}Return" type="{$function.returntype|xsdtype()|washxml()}"/>
</message>

{/foreach}

<!-- port name and binding  -->

<portType name="{$servicename}PortType">
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

    <operation name="{$fname}">
        <documentation>{$function.documentation|washxml()}</documentation>
        <input message="tns:{$fname}Request"/>
        <output message="tns:{$fname}Response"/>
    </operation>
{/foreach}

</portType>

<binding name="{$servicename}SOAPBinding" type="tns:{$servicename}PortType">
    <soap:binding
      style="rpc"
      transport="http://schemas.xmlsoap.org/soap/http"/>
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

    <operation name="{$fname}">
        <soap:operation soapAction="urn:{$fname}Action"/>
        <input>
            <soap:body
              namespace="urn:{$fname}"
              use="encoded"
              encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
        </input>
        <output>
            <soap:body
              namespace="urn:{$fname}"
              use="encoded"
              encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
        </output>
    </operation>
{/foreach}
</binding>

<!-- services -->
<service name="{$servicename}Service">
    <port name="{$servicename}Port" binding="tns:{$servicename}SOAPBinding">
        <soap:address location="{'webservices/execute/phpsoap'|ezurl(no, full)}" />
    </port>
</service>

</definitions>