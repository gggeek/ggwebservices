<?php /*

[TemplateSettings]
ExtensionAutoloadPath[]=ggwebservices

[RegionalSettings]
TranslationExtensions[]=ggwebservices

[RoleSettings]
# The following views do permissions checking on their own, based on the executed webservice
PolicyOmitList[]=webservices/execute
PolicyOmitList[]=webservices/wsdl
PolicyOmitList[]=webservices/xsd

# Cache item entry (for eZ Publish 4.3 and up)
[Cache]
CacheItems[]=webservices

[Cache_webservices]
name=ggWebservices server-side wsdl cache
path=webservices

*/ ?>