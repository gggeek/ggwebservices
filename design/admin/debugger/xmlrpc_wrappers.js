/**
 * JS-XMLRPC "wrapper" functions
 * Generate stubs to transparently access xmlrpc methods as js functions
 *
 * @version $Id: xmlrpc_wrappers.js,v 1.1 2007/01/22 23:15:22 ggiunta Exp $
 * @copyright G. Giunta (C) 2006
 * @author Gaetano Giunta
 */

/**
* Given an xmlrpc client and a method name, register a js wrapper function
* that will call it and return results using native js types for both
* params and results. The generated js function will return an xmlrpcresp
* oject for failed xmlrpc calls
*
* Known limitations:
* - server must support system.methodsignature for the wanted xmlrpc method
* - for methods that expose many signatures, only one can be picked (we
*   could in priciple check if signatures differ only by number of params
*   and not by type, but it would be more complication than we can spare time)
* - nested xmlrpc params: the caller of the generated js function has to
*   encode on its own the params passed to the js function if these are structs
*   or arrays whose (sub)members include values of type datetime or base64
*
* Notes: the connection properties of the given client will be copied
* and reused for the connection used during the call to the generated
* js function.
* Calling the generated js function 'might' be slow: a new xmlrpc client
* is created on every invocation and an xmlrpc-connection opened+closed.
* An extra 'debug' param is appended to param list of xmlrpc method, useful
* for debugging purposes.
*
* @param xmlrpc_client client     an xmlrpc client set up correctly to communicate with target server
* @param string        methodname the xmlrpc method to be mapped to a js function
* @param array         extra_options array of options that specify conversion details. valid ptions include
*        integer       signum     the index of the method signature to use in mapping (if method exposes many sigs)
*        integer       timeout    timeout (in secs) to be used when executing function/calling remote method
*        string        protocol   'http' (default), 'http11' or 'https'
*        string        new_function_name the name of js function to create. If unsepcified, lib will pick an appropriate name
*        string        return_source if true return js code w. function definition instead fo function name
*        bool          encode_js_objs let js objects be sent to server using the 'improved' xmlrpc notation, so server can deserialize them as js objects
*        bool          decode_js_objs --- WARNING !!! possible security hazard. only use it with trusted servers ---
*        mixed         return_on_fault a js value to be returned when the xmlrpc call fails/returns a fault response (by default the xmlrpcresp object is returned in this case). If a string is used, '%faultCode%' and '%faultString%' tokens will be substituted with actual error values
*        bool          debug      set it to 1 or 2 to see debug results of querying server for method synopsis
* @return string                  the name of the generated js function (or false) - OR AN ARRAY...
* @access public
*/
function wrap_xmlrpc_method(client, methodname, extra_options)
{
  return build_remote_method_wrapper_code(client, methodname);
}

/**
* Given the necessary info, build js code that creates a new function to
* invoke a remote xmlrpc method.
* Take care that no full checking of input parameters is done to ensure that
* valid js code is emitted.
* @access private
*/
function build_remote_method_wrapper_code(client, methodname, xmlrpcfuncname,
	msig, mdesc, timeout, protocol, client_copy_mode, prefix,
	decode_js_objects, encode_js_objects, decode_fault,
	fault_response)
{
  return {'source': '', 'docstring': '/** Automatic method stub generation yet to be implemented **/'};
}
