GG Webservices extension for eZ Publish
=======================================

An extension that adds/improves the native capabilities of eZ Publish to work as

- webservices server
- webservices client

with a visual webservices debugger thrown in for good measure


Features
--------

Allows to easily execute calls to remote servers using JSONRPC, XMLRPC and SOAP
protocols or REST schemes (both from within php code and templates)

Allows to expose existing functionality as JSONRPC or XMLRPC, REST and (better
than eZ native) SOAP webservices, similar to what can already be done with SOAP
and eZJSCore in any eZ Publish installation

Allows to expose functionality as webservice regardless of the protocol used by
the client - without having to duplicate code

Provides a proxy module allowing javascript code served by an eZ Publish server
to execute cross-domain webservice calls (without resorting to jsonp or html tricks)
and even cross-protocol calls

Allows easy debugging of webservice calls by providing a complete graphical
interface for debugging

Adds support for WSDL in SOAP calls (both server-side and client-side)

Improves the existing http client adding features such as support for more
authentication schemes, compressed requests etc...

Allows usage of JSONRPC or XMLRPC (and eventually SOAP) to call functions exposed
via the ezjscore extension API

Keeps the same API as the existing eZP SOAP classes for maximum interoperability

And much more :-)

Prerequisites
-------------

- PHP 5 / eZP 4.0+
- to execute/receive jsonrpc calls, the php JSON extension is needed
- to execute/receive rest calls, the php SIMPLEXML or JSON extensions are needed
- to execute/receive xmlrpc calls, the php XMLRPC extension is needed
- to call remote services using wsdl descriptions the php SOAP extension is needed
- to integrate with the ezjscore extension, eZP 4.2 is needed (and the ezjscore
  extension too, of course)
- to make ajax calls to the server itself using the integrated javascript library
  the ezjscore extension is recommended. Any other javascript library for xmlrpc/
  jsorpc can also do.
- to receive soap/xmlrpc/jsonrpc/rest+post calls from users authenticated via the
  standard session mechanism (such as e.g. ajax calls), the ezformtoken extension
  has to be disabled

Basics
------
Server (eZPublish exposes webservices):

- to receive xmlrpc, jsonrpc or 'rest' calls, you need to:
    1. modify the configuration in ``wsproviders.ini`` and possibly in ``site.ini.append.php``
    2. create a file ``extension/<xxx>/<jsonrpc|xmlrpc|rest>/initialize.php`` with the
       php code to be exposed as webservice
    3. manage access control to the exposed services via the administration interface
       and/or configuration settings

Client (eZPublish accesses webservices exposed by other systems):

- every remote webservice server that has to be accessed from the eZ Publish
  server itself has to be defined in ``wsproviders.ini``
- to call remote webservices from within templates use the template fetch function
  ``fetch( 'webservices', 'call', hash( paramname, value, ... ) )``
  Please remember to desactivate the view cache where needed for node templates
  that execute webservice calls
- to call remote webservices from php code use
  ``ggeZWebServicesClient::call( $server, $metod, $params=array(), $options=array() );``

For more details on usage of the extension, look in the doc/ directory.


Known limitations
-----------------

- support for WSDL is limited, as is support for SOAP 1.2
- jsonrpc support is only available for version 1.0, not for 2.0
- the ws client does not support automatic cookie management
- the ws client does not support oauth authentication
- the proxy view works exposing an xmlrpc+jsonrpc api. This means that it can not
  be used to emulate the richer semantics of soap/rest calls. E.g. at the moment
  it can not be used to send POST calls to upstream rest webservices
- rest support is far from perfect, as the extension was developed based on rpc
  semantics (call a *method* which takes a *hash of parameters*).
  Currently:
    * for the rest client, the parameters will be translated into query
      string or http request payload, not into a slash-separated part of the url.
    * for the rest server, there is no support for having slash-separated
      parameters or complex routing rules
- and much more: read doc/todo to get a detailed list of bits which could improve


FAQ
---
  Q: how do I debug webservices without going insane?
  
  A1: you're a lucky guy, since this extension provides a nice graphical debugger
      within the standard admin interface
  
  A2: when DebugSettings/DebugOutput and TemplateSettings/DevelopmentMode are
      both enabled, the requests sent by the fetch function webservices/call
      will be displayed as part of the standard debug output
  
  A3: by enabling logging, you n00b!
      There is an ini setting in wsproviders.ini that controls verbosity for a
      log file dedicated to webservices: var/<vardir>/log/webservices.log

  Q: how secure is this extension?
  
  A: the main author prides himself with being an extremely security-focused
     developer - he learnt many things about webservices and security in the
     infamous "lupii worm" incident, and later even worked as security consultant
     for a short time.
     Having said that, the codebase is by now quite large, and the amount of
     functionality available staggering.
     There is no guarantee whatsoever, either implicit or explicit, that using
     it will not expose your data, or data of your customers, to fraudulent use.
     If this poses a problem to you, please sponsor a security audit.
