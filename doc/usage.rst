Usage instructions for the GG Webservices extension for eZ Publish
==================================================================

SERVER-SIDE: exposing your webservices
--------------------------------------

5. to receive jsonrpc or xmlrpc or rest webservice calls:

   + define the php functions you want to expose as webservices in a file
     ``initialize.php`` in either a ``phpxmlrpc``, ``jsonrpc`` or ``rest`` folder
     in the eZ Publish installation root folder (or in a ``phpxmlrpc``, ``jsonrpc``
     or ``rest`` folder inside an extension that is listed in the JSONRPCExtensions /
     XMLRPCExtensions / RESTExtensions array setting of ini file *wsproviders.ini*).
     Look up code in the existing initialize.php files as examples of valid syntax

   + make sure the ``EnableJSONRPC`` or ``EnableXMLRPC`` or ``EnableREST`` setting
     (or any combination thereof) in config file wsproviders.ini has a value of
     ``true`` (without the quotes)

   + the url used to access the webservices will be like this:
     http://<your.server>/webservices/execute/<jsonrpc>
     or
     http://<your.server>/<ezpublish-root>/index.php/<siteaccess>/webservices/execute/<jsonrpc>
     (depending on your eZ Publish setup - vhost mode or not).
     The last part of the url corresponds of course to the protocol: xmlrpc,
     jsonrpc, rest, soap or phpsoap

   + give to the eZ Publish user who needs to execute webservice calls permission
     on the 'webservices/execute' function in the 'roles and policies' panel in
     the administration interface.

     A good starting point is to give to the Anonymous role access limited to:
     - system.listMethods
     - system.methodHelp
     - system.methodSignature
     With this, non-logged-in-users will be able to see the complete list of
     available webservices and their signature, but execute none.

     NB: non-logged-in-users includes the calls which are sent from the debugger
     to the server itself, unless you manually add your session cookie in the
     client options in the debugger.

    Please note: the permission on webservice 'system.multiCall' allows
    execution of ANY defined webservice without further permission checking
    on the encapsulated webservices

  + if your webservice client is remote (non-ajax) and it does not support
    session cookies, you can still use auth mechanisms to allow	it to log-in and
    execute calls:

    I) using fixed-IPs

       If the client makes calls from a known (list of) fixed IP address
	   a. edit the value of the settings ValidateClientIPs and ValidClientIPs in
	      wsproviders.ini. This way no other client IP will be allowed
       b. in the 'roles and policies' panel in the administration interface, give
	      full access to 'webservices/execute' to the Anonymous role

    II) adding an eZP username/password in every webservice request

	    a. give to the Anonymous role access to the ezp.authandexec webservice
	    b. create a user account for your ws client, assign it to a new role
	    c. assign to this new role access to the desired webservices
        d. to execute webservice 'test' with parameters 'p1', 'p2', the client
	       shall call the following webservice:
           ezp.authandexec( 'username', 'password', 'test', array( 'p1', 'p2' ) )
        e. use the built-in debugger to help with troubleshooting

5b. an alternative to receive jsonrpc or xmlrpc webservice calls:

    + copy the files jsonrpc.php/xmlrpc.php to the eZ Publish installation root
      folder

    + modify the Apache rewrite rules / the .htaccess file so that they are
      accessible from the web

    + define the php functions you want to expose as webservices in the file
      "initialize.php" in either a "phpxmlrpc" or "jsonrpc" folder in the
      eZ Publish installation root folder (or in a "phpxmlrpc" or "jsonrpc"
      folder inside an extension that is listed in the JSONRPCExtensions/
      XMLRPCExtensions array setting of ini file wsproviders.ini)

    + make sure the EnableJSONRPC or EnableXMLRPC setting (or both) in file
      wsproviders.ini has a value of "true" (without the quotes)

    + the url used to access the webservices will depend on your
      rewrite rules. If no rewrite rules are in use, it will be like this:
      http://<your.server>/<ezpublish-root>/jsonrpc.php

    + optionally, set up auth mechanism in your web server to access the new urls
      (e.g. BASIC or DIGEST auth)

    + the difference with point 3 above is a slightly more complex install vs. a
      smaller execution time and memory usage. In this configuration it is also
      not possible to assign execution permissions to callers - all webservices
      will be available to everybody. TAKE CARE!

6. to enable js code in the browser to call webservices on the local server

   + create the desired webservices, as per point 5 or 5b above

   + use either

    - the ``Y.io.jsonrpc( string method, array params, object config )``
      javascript function, available in the io-jsonrpc yui module or the
      ``$.jsonrpc( string method, array params, object config )``
      javascript function available for JQuery (both support only jsonrpc,
      not xmlrpc)

    - the set of javascript classes defined in the JS-XMLRPC library
      (docs for it are available at http://phpxmlrpc.sourceforge.net/jsxmlrpc/javadoc/)

    For how to load the js libraries needed for those calls, look in the doc/samples
    folder.

7. to enable usage of xmlrpc/jsonrpc protocols to call functions that have
   already been made available via the jscore extension:

   + make sure the JscoreIntegration parameter is set to "enabled" in wsproviders.ini.append.php

   + make sure the current eZ Publish user has access rights to invoke the needed
     ezjscore functions

   + the ezjscore functions will be automatically made available to jsonrpc and/or
     xmlrpc clients depending on the value of the EnableJSONRPC or EnableXMLRPC setting

   + it is even possible to use the jsonrpc protocol from the browser to access
     jscore functions instead of the native javascript serialization by usage of
     the Y.io.jsonrpc / $.jsonrpc javascript functions


CLIENT-SIDE: calling webservices on remote servers
--------------------------------------------------

8. to make webservice calls to external servers via templates:

   + define the remote servers that will be made accessible in the
     wsproviders.ini.append.php file

   + use the fetch( 'webservices', 'call', hash( ... ) ) template fetch function.
     It takes 4 parameters in the hash:
     - server: name of the remote server
     - method: name of the ws method to execute
     - parameters: array of ws method parameters (optional)
     - options: array of extra options for the client (otional)

   + raise the 'Logging' parameter in wsproviders.ini file to 'info' to have
     complete traces of webservice communication in var/<vardir>/log

9. to make webservice calls to external servers via php code:

   + define the remote servers that will be made accessible in the
     wsproviders.ini.append.php file

   + use the php function
   ggeZWebServicesClient::call( $server, $metod, $params=array(), $options=array() );

10. to enable js code in the browser to call webservices on remote servers
    (cross domain requests) without the need for flash or other advanced techniques:

   + define the remote servers that will be made accessible, in the
     wsproviders.ini.append.php file

   + give access permissions to the webservices/proxy function to the desired
     eZ Publish user, possibly with a limitation on remote server

   + the url to be called is:
     http://<my.ez.server>/index.php/<siteaccess>/webservices/proxy/$protocol/$remoteservername
     where protocol is either "phpxmlrpc" or "jsonrpc", and remoteservername is
     the name of a server defined in wsproviders.ini.append.php file (the remote
     server can use a different protocol, such as soap, from the one used by the
     browser)

   + for easy ajax implementation, use the $.wsproxy( servername, method, params, callback ) or
     Y.io.wsproxy( servername, method, params, callback ) methods, which are made available
     by this extension.
     To load them, use:
     {ezscript_require( array( 'ezjsc::jquery', 'ggwstemplate::jquery::json', 'ggwstemplate::jquery::jsonrpc' ) )}
     or
     {ezscript_require( array( 'ezjsc::yui3', 'ggwstemplate::yui3::jsonrpc' ) )}

11. more information is available in the api.rst file
