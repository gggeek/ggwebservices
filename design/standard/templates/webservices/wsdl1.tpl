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
{def $ptype = ''
     $rtype = ''
     $typename = ''
     $newtypes = array()}
{foreach $functions as $fname => $function}
    {set $fname = $fname|washxml()}

<wsdl:message name="{$fname}Request">
{foreach $function.params as $name => $type}
    {set $ptype = $type|xsdtype('tnsxsd:')}
    {if $ptype|begins_with('tnsxsd:')}
        {if $newtypes|contains($ptype)|not()}
            {set $newtypes = $newtypes|append($ptype)}
        {/if}
        {set $ptype = 'tnsxsd:'|append($ptype|explode(':')|extract(1)|implode('_'))}
    {/if}

    <wsdl:part name="{$name}" type="{$ptype|washxml()}"/>
{/foreach}

</wsdl:message>

<wsdl:message name="{$fname}Response">
    {set $rtype = $function.returntype|xsdtype('tnsxsd:')}
    {if $rtype|begins_with('tnsxsd:')}
        {if $newtypes|contains($rtype)|not()}
            {set $newtypes = $newtypes|append($rtype)}
        {/if}
        {set $rtype = 'tnsxsd:'|append($rtype|explode(':')|extract(1)|implode('_'))}
    {/if}
    {if ne($rtype, '')}
        <wsdl:part name="{$fname}Return" type="{$rtype|washxml()}"/>
    {/if}

</wsdl:message>

{/foreach}
{undef $rtype $ptype}

{if $newtypes|count()}

<!-- types -->
<wsdl:types>
    <xsd:schema
      xmlns="http://www.w3.org/2001/XMLSchema"
      targetNamespace="{$namespace|append('/types')|washxml()}">
        {if $externalxsd}
        <xsd:import
          namespace="{$namespace|append('/types')|washxml()}"
          schemaLocation="{concat('webservices/xsd/',$service)|ezurl(no, full)}"/>
        {else}
            {foreach $newtypes as $type}
                {set $typename = $type|explode(':')|extract(1)|implode('_')}
                {if $type|begins_with('tnsxsd:arrayOf')}
                    {include uri='design:webservices/xsd/array.tpl' typename=$typename basetype=$type|extract(14) soapencns='SOAP-ENC'}
                {elseif $type|begins_with('tnsxsd:choiceOf')}
                    {include uri='design:webservices/xsd/choice.tpl' typename=$typename basetypes=$type|extract(15)|explode('Or')}
                {elseif $type|begins_with('tnsxsd:class')}
                    {include uri='design:webservices/xsd/class.tpl' typename=$typename basetype=$type|extract(12)}
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