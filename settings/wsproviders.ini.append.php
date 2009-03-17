<?php /*

[GeneralSettings]
# Logging of outgoing webservice calls
Logging=warning
# Incoming webservice calls
# If enabled, the instance's default siteaccess will always be used.
# If disabled, the matched siteaccess (host, port, etc) will be used, allowing
# the usage of webservices when multiple ezpublish websites are hosted on one instance
UseDefaultAccess=enabled

# enable reception of incoming webservice calls
EnableJSONRPC=false
EnableXMLRPC=false

[ExtensionSettings]
# list of extensions providing webservice functionality
JSONRPCExtensions[]
XMLRPCExtensions[]

### definition of webservice servers that can be called by template or php code

#[myserver]
#providerUri=http://my.test.server/wsendpoint.php
#providerType=JSONRPC, SOAP, REST or XMLRPC
#providerUsername=
#providerPassword=
#timeout=60

### definition of webservice servers that can be called by js code using the wsproxy module

#[proxy_myserver]
#providerUri=http://my.test.server/wsendpoint.php
#providerType=JSONRPC, SOAP, REST or XMLRPC
#providerUsername=
#providerPassword=
#timeout=60
#providerMethods= a csv list of remote methods that can be called. use 'any' to specify an open proy, ie. all methods will accepted

*/ ?>