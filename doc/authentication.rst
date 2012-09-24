Dealing with authentication when exposing webservices
=====================================================

Neither XMLRPC, nor JSONRPC or REST protocols specify authentication/authorization
methods.

There are different possibilities for configuring acess control to webservices
implemented by this extension. Here is a short introduction.

Authorization
-------------
Authorization is managed via the standard "Roles & Policies" subsystem.

. Every user group can be given access to the webservices/execute access function,
  with limitations on every webservice method defined

. A second limitation which can be used when creating a policy for webservices/execute
  is relative to the siteaccess used when calling the service.
  This is useful if you want to dedicate a siteaccess exclusively to exposing
  webservices, and protect it via https, http authorization (basic or digest)
  or other means

. There is no explicit support to deny access to a given service when using a
  specific protol, while allowing access for other protocols.
  In other words: if method "ezp.authandexec" is available for both jsonrpc and
  xmlrpc calls, a user can either access it using both protocols or not at all.
  The simple workaround is not to declare the method in the initialize.php
  file for a given protocol.

. A "master switch" is used to enable/disable individually access via any of the
  supported protocols.
  It is found in wsproviders.ini, section ``GeneralSettings``.
  Note that by default when activating this extensions 3 protocols are activated:
  xmlrpc, jsonrpc and rest

. Access to the webservices/proxy view can only be limited based on the remote
  servers which are proxied, e.g. one role might be given rights to access the
  services on flickr and another the services on twitter

Authentication
--------------
Different configurations are possible

1. anon auth: easy peasy. Giving access to exposed webservices to Anonymous role
   is done via the Administration Interface

2. anon auth + within-call credentials: the webservice "ezp.authandexec" allows
   the caller to add username+password inside the payload, and wrap in this call
   the call to the actual method.
   This forces the client to send username+pwd on every request, just like "http
   basic auth"; it does not need the client to manage session cookies

3. session-based auth: uses a standard ezp session based on cookies.
   This is most practical for clients based on browser-technology, i.e. ajax calls
   Note: no specific webservice is provided to initiate the session and get back
   the cookie

4. IP-based auth: when enabled, it blocks calls coming from IP addresses not whitelisted.
   It can of course be complemented with a blanket anon-auth configuration to
   allow calls from any client connecting from a given IP

5. basic auth, digest auth: these can be configured in the webserver itself, and
   the extension does not mingle with it.
   Same with https.

   If you are now asking yourself the very interesting question:
   "but... can I let Apache enforce Basic authentication and still use the
   logins/passwords of eZPublish user accounts to manage fine-grained authorization?"
   Then rejoyce because the answer is Yes!
   In order to have Apache enforce Basic authentication and connect it
   to eZ Publish user accounts, you just need the ezapacheauth extension, from
   http://projects.ez.no/ezapacheauth

6. oauth is not supported. Look at the native REST support of eZ Publish if you
   need it
