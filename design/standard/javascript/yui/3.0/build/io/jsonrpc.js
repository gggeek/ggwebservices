
/**
* JSON-RPC client for yui 3 - for easy ajax calls to the eZPublish server
* API based on Y.io.ez for maximum interoperability (but not identical!)
* Works both if included as plain javascript file or if parsed as javascript-generating
* template (see jquery.tpl on how to do that).
* In the first case, the var Y.io.ez.url should be set up with the url of the root
* of the eZ Publish installation _before_ including this
*
* @author G. Giunta
* @copyright (c) 2009-2012 G. Giunta
* @license code licensed under the GPL License: see LICENSE file
*
* @todo use closures instead of saving stuff around for later
*/
//{literal}
YUI( YUI3_config ).add('io-jsonrpc', function( Y )
{
    // this looks weird but is ok, it is just needed for writing meta-js via tpl
    // and not passing it through the template system...
    var _serverUrl = '{/literal}{"/"|ezurl("no", "full")}{literal}', _configBak;
    if ( '{' + '/literal}{"/"|ezurl("no", "full")}{literal}' == _serverUrl )
    {
        if ( typeof Y.io !== "undefined" && typeof Y.io.ez !== "undefined" && typeof Y.io.ez.url !== "undefined" )
        {
            _serverUrl = Y.io.ez.url.replace( '/ezjscore/', '' );
        }
        else
        {
            _serverUrl = ''; // @todo find a better default?
        }
    }

    Y.io.jsonrpc = function ( callMethod, callParams, c )
    {
        var url = _serverUrl + '/webservices/execute/jsonrpc';

        // force POST method, allow other configs to be passed down
        if ( c === undefined )
            c = {on:{}, data: '', headers: {'Content-Type': 'application/json; charset=UTF-8'}, method: 'POST'};
        else
            c = Y.merge( {on:{}, data: ''}, c, {headers: {'Content-Type': 'application/json; charset=UTF-8'}, method: 'POST'} );

        // encode function arguments as post params
        c.data = Y.JSON.stringify( {method: callMethod, params: callParams, id: 1} );

        // force json transport
        c.headers.Accept = 'application/json,text/javascript,*/*';

        // backup user success call, as we inject our decoding success call
        if ( c.on.success !== undefined )
            c.on.successCallback = c.on.success;
        c.on.success = _iojsonrpcSuccess;
        // and backup the config object too
        _configBak = c;

        return Y.io( url, c );
    }

    Y.io.wsproxy = function ( remoteServer, callMethod, callParams, c )
    {
        var url = _serverUrl + '/webservices/proxy/jsonrpc/' + remoteServer;

        // force POST method, allow other configs to be passed down
        if ( c === undefined )
            c = {on:{}, data: '', headers: {'Content-Type': 'application/json; charset=UTF-8'}, method: 'POST'};
        else
            c = Y.merge( {on:{}, data: ''}, c, {headers: {'Content-Type': 'application/json; charset=UTF-8'}, method: 'POST'} );

        // encode function arguments as post params
        c.data = Y.JSON.stringify( {method: callMethod, params: callParams, id: 1} );

        // force json transport
        c.headers.Accept = 'application/json,text/javascript,*/*';

        // backup user success call, as we inject our decoding success call
        if ( c.on.success !== undefined )
            c.on.successCallback = c.on.success;
        c.on.success = _iojsonrpcSuccess;
        // and backup the config object too
        _configBak = c;

        return Y.io( url, c );
    }

    function _iojsonrpcSuccess( id, o )
    {
        if ( o.responseJSON === undefined )
        {
            // create new object to avoid error in ie6 (and do not use Y.merge since it fails in ff)
            // the members of responseJSON are the same as those used by Y.io.ez
            var returnObject = {'responseJSON': null,
                                'readyState':   o.readyState,
                                'responseText': o.responseText,
                                'responseXML':  o.responseXML,
                                'status':       o.status,
                                'statusText':   o.statusText
            };
            var response;
            try {
                response = Y.JSON.parse( o.responseText );
                /// @todo we should check that either result or error are null...
                // we return in responseJSON both the jsonrpc-style members and the ezjscore ones
                response.content = response.result;
                response.error_text = response.error;
                returnObject.responseJSON = response;
            } catch ( error ) {
                var c = _configBak;
                if ( c.on.failure !== undefined )
                {
                    returnObject.statusText = error.message + ' error in file ' + error.fileName + ' line ' + error.lineNumber;
                    // shall we also patch status to something else than 200?
                    c.on.failure( id, returnObject );
                    return;
                }
                else
                {
                    throw error;
                }
            }
        }
        else
        {
            var returnObject = o;
        }

        var c = _configBak;
        if ( c.on.successCallback !== undefined )
        {
            c.on.successCallback( id, returnObject );
        }
        else if ( window.console !== undefined )
        {
            if ( returnObject.responseJSON.error_text != null )
                window.console.error( 'Y.io.jsonrpc(): ' + Y.JSON.stringify(returnObject.responseJSON.error_text) );
            else
                window.console.log( 'Y.io.jsonrpc(): ' + Y.JSON.stringify(returnObject.responseJSON.content) );
        }
    }

    //_jsonrpc.url = _serverUrl;
    //Y.io.jsonrpc = _jsonrpc;
}, '3.0.0', {requires:['io-base', 'json']});
//{literal}