<?xml version='1.0' encoding='UTF-8'?>
{**
 * WSDL 1 template
 *
 * @todo allow user to specify 'location', via eg. an ini value
 *}
<definitions

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

<portType name="{$servicename|washxml()}PortType">
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

    <operation name="{$fname}">
        <documentation>{$function.documentation|washxml()}</documentation>
        <input message="tns:{$fname}Request"/>
        <output message="tns:{$fname}Response"/>
    </operation>
{/foreach}

</portType>

<binding name="{$servicename|washxml()}SOAPBinding" type="tns:{$servicename|washxml()}PortType">
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
<service name="{$servicename|washxml()}Service">
    <port name="{$servicename|washxml()}Port" binding="tns:{$servicename|washxml()}SOAPBinding">
        <soap:address location="{'webservices/execute/phpsoap'|ezurl(no, full)}" />
    </port>
</service>

</definitions>