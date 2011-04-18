<?xml version='1.0' encoding='UTF-8'?>
{**
 * WSDL 1 template
 *}
<wsdl:definitions

  name="{$servicename|washxml()}"
  targetNamespace="{$namespace|washxml()}"
  xmlns:tns="{$namespace|washxml()}"
  xmlns:tnsxsd="{$namespace|append('/types')|washxml()}"

  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
  xmlns="http://schemas.xmlsoap.org/wsdl/">

<!-- imports -->
{foreach $imports as $service}
    {set $service=concat('webservices/wsdl/', $service)|ezurl(no, full)}
<wsdl:import namespace="{$service}" location="{$service}"/>

{/foreach}

<!-- messages -->
{def $ptype=''
     $rtype=''
     $types = array()}
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

<wsdl:message name="{$fname}Request">
{foreach $function.params as $name => $type}
    {set $ptype = $type|xsdtype()}
    {if $ptype|begins_with('tnsxsd:')}
        {if $types|contains($ptype)|not()}
            {set $types = $types|append($ptype)}
        {/if}
    {/if}

    <wsdl:part name="{$name}" type="{$ptype|washxml()}"/>
{/foreach}

</wsdl:message>

<wsdl:message name="{$fname}Response">
    {set $rtype = $function.returntype|xsdtype()}
    {if $rtype|begins_with('tnsxsd:')}
        {if $types|contains($rtype)|not()}
            {set $types = $types|append($rtype)}
        {/if}
    {/if}
    <wsdl:part name="{$fname}Return" type="{$rtype|washxml()}"/>
</wsdl:message>

{/foreach}
{undef $rtype $ptype}

{if $types|count()}

<!-- types -->
<wsdl:types>
    <xsd:schema
      xmlns="http://www.w3.org/2001/XMLSchema"
      targetNamespace="{$$namespace|append('/types')|washxml()}">
        {if $externalxsd}
        <xsd:import
          namespace="{$$namespace|append('/types')|washxml()}"
          schemaLocation="{concat('webservices/xsd/',$service)|ezurl(no, full)}"/>
        {else}
            {foreach $types as $type}
                {set $type = $type|extract(7)}
                {if $type|begins_with('arrayOf')}
                    {include uri='design:webservices/xsd/array.tpl' typename=$type basetype=$type|extract(7)}
                {elseif $type|begins_with('choiceOf')}
                    {include uri='design:webservices/xsd/array.tpl' typename=$type basetypes=$type|extract(8)|explode('Or')}
                {elseif $type|begins_with('class')}
                    {include uri='design:webservices/xsd/class.tpl' typename=$type basetype=$type|extract(5)}
                {else}
                    {* @todo ... *}
                {/if}

            {/foreach}
        {/if}

    </xsd:schema>
</wsdl:types>

{/if}

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
        <soap:address location="{'webservices/execute/phpsoap'|ezurl(no, full)}"/>
    </wsdl:port>
</wsdl:service>

</wsdl:definitions>