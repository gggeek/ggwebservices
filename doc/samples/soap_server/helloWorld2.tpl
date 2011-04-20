<?xml version='1.0' encoding='UTF-8'?>
{**
* Sample wsdl 1.1 file generated via template.
* The only variable part is in fact the address in the binding.
*}
<wsdl:definitions
  name="SOAP"
  targetNamespace="http://localhost/ezp/installs/ezpublish-4.4.0/index.php/eng/webservices/wsdl"
  xmlns:tns="http://localhost/ezp/installs/ezpublish-4.4.0/index.php/eng/webservices/wsdl"
  xmlns:tnsxsd="http://localhost/ezp/installs/ezpublish-4.4.0/index.php/eng/webservices/wsdl/types"

  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
  xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
  xmlns="http://schemas.xmlsoap.org/wsdl/">

<!-- messages -->
<wsdl:message name="helloWorld2Request">
    <wsdl:part name="user" type="xsd:string"/>
    <wsdl:part name="password" type="xsd:string"/>
    <wsdl:part name="whatever" type="xsd:int"/>
</wsdl:message>

<wsdl:message name="helloWorld2Response">
    <wsdl:part name="helloWorldReturn" type="xsd:anyType"/>
</wsdl:message>

<!-- types -->
<wsdl:types>
    <xsd:schema
      xmlns="http://www.w3.org/2001/XMLSchema"
      targetNamespace="http://localhost/ezp/installs/ezpublish-4.4.0/index.php/eng/webservices/wsdl/types">
</wsdl:types>

<!-- port name and binding  -->

<wsdl:portType name="SOAPPortType">
    <wsdl:operation name="helloWorld2">
        <wsdl:documentation></wsdl:documentation>
        <wsdl:input message="tns:helloWorld2Request"/>
        <wsdl:output message="tns:helloWorld2Response"/>
    </wsdl:operation>
</wsdl:portType>

<wsdl:binding name="SOAPSOAPBinding" type="tns:SOAPPortType">
    <soap:binding
      style="rpc"
      transport="http://schemas.xmlsoap.org/soap/http"/>
    <wsdl:operation name="helloWorld2">
        <soap:operation soapAction="urn:helloWorld2Action"/>
        <wsdl:input>
            <soap:body
              namespace="urn:helloWorld2"
              use="encoded"
              encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
        </wsdl:input>
        <wsdl:output>
            <soap:body
              namespace="urn:helloWorld2"
              use="encoded"
              encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
        </wsdl:output>
    </wsdl:operation>
</wsdl:binding>

<!-- services -->
<wsdl:service name="SOAPService">
    <wsdl:port name="SOAPPort" binding="tns:SOAPSOAPBinding">
        <soap:address location="{'webservices/execute/phpsoap'|ezurl(no, full)}"/>
    </wsdl:port>
</wsdl:service>

</wsdl:definitions>