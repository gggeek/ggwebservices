SOAP FAQ:

Q: the generated wsdl points to xxx/webservices/execute/phpsoap, but I use the custom soap controller with a different url
A: just override the template used to generate the wsdl, in design/standard/templates/webservices/wsdl1.tpl

Q: can I register a webservice and provide my own custom wsdl?
A: yes, you can. You can provide either a template or the complete wsdl file contents.
   Just pass it to the registerFunction() method, as 2nd parameter

Q: is it better to use the custom soap controller or the /webservices/execute/phpsoap view?
A: there are some advantages to both:
   . the custom soap controller is probably faster and uses less resources
   . the /webservices/execute/phpsoap view allows more finer-grained permissions checking
     (such as validating by client-ip-addrress or by single webservice), and it also
     allows to register for soap access functions exposed as xmlrpc/jsonrpc methods

Q: if I use the custom soap controller, will wsdl be generated for my services?
A: yes
