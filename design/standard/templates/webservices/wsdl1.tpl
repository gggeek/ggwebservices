<?xml version='1.0' encoding='UTF-8'?>
{**
 * WSDL template
 *
 * @todo import or merge $extras
 * @todo add definition of types for complex types
 * @todo fix namespaces
 *}
<definitions

  name="TOBEDONEWSNAME"
  targetNamespace="urn:WSNAME..."
  xmlns:typens="urn:WSNAME..."

  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
  xmlns="http://schemas.xmlsoap.org/wsdl/">

<!-- imports -->

<!-- types -->

{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

<!-- messages -->
<message name="{$fname}Request">
    {foreach $function.params as $name => $type}
        <part name="{$name}" type="{$type|xsdtype()}"/>
    {/foreach}
</message>

<message name="{$fname}Response">
    <part name="{$fname}Return" type="{$function.returntype|xsdtype()}"/>
</message>

<!-- bindings and port names -->

<portType name="{$fname}PortType">
    <operation name="{$fname}">
        <documentation>{$function.documentation|washxml()}</documentation>
        <input message="typens:{$fname}Request"/>
        <output message="typens:{$fname}Response"/>
    </operation>
</portType>

<binding name="{$fname}Binding" type="typens:{$fname}PortType">
    <soap:binding
      style="rpc"
      transport="http://schemas.xmlsoap.org/soap/http"/>
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
</binding>

<!-- services -->
<service name="{$fname}Service">
    <port name="{$fname}Port" binding="typens:{$fname}Binding">
            <soap:address location="{'webservices/execute/phpsoap'|ezurl(no, full)}" />
    </port>
</service>

{/foreach}

</definitions>