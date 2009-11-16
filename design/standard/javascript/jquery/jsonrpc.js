
/**
* JSON-RPC client for jquery
* API based on ezjscore's client for maximum interoperability (but not identical!)
* Uses jquery.json plugin for json (de)serializing
* Works both if parsed as javascript-generating template or if included as plain javascript file.
* In the latter case, the var Y.ez.url should be set up with the url of the root
* of the eZ Publish installation
*
* @author G. Giunta
* @copyright (c) 2009 G. Giunta
* @version $Id$
*
* @todo use closures instead of saving stuff around for later
*/
//{literal}
(function($) {

    // this looks weird but is ok, it is just needed for writing meta-js via tpl
    // and not passing it through the teemplate system...
    var _serverUrl = '{/literal}{'/'|ezurl('no', 'full')}{literal}', _configBak;
    if ( '{' + "/literal}{'/'|ezurl('no', 'full')}{literal}" == _serverUrl )
    {
        if ( typeof $.ez.url != undefined )
        {
            _serverUrl = $.ez.url.replace( '/ezjscore/', '' );
        }
        else
        {
            _serverUrl = ''; // @todo find a better default?
        }
    }

    $.jsonrpc = function _jsonrpc( callMethod, callParams, options )
    {
        var url = _serverUrl + '/webservices/execute/jsonrpc';

        // backup user callback functions, as we inject our decoding success call
        if ( options !== undefined )
            _configBak = options;

        // force json transport
        var c = {
            contentType: 'application/json',
            data: $.toJSON( { method: callMethod, params: callParams, id: 1 } ),
            dataType: 'text', // avoid having jquery parsing response json using eval
            accepts: { text: 'application/json,text/javascript' }, // fix the accept header
            processData: false,
            success: _iojsonrpcSuccess,
            error: options.error,
            type: 'POST',
            url: url
        };
        return $.ajax( c );
    };

    function _iojsonrpcSuccess( data, textStatus )
    {
        //response = { 'content': null, 'error_text': 'parsing of response to be done...' };
        var returnObject = {'responseJSON': null,
                            'readyState':   4, // is this always correct?
                            //'responseText': data,
                            'responseXML':  '',
                            'status':       200, // is this always correct?
                            'statusText':   textStatus };

        var response;
        try {
            response = $.secureEvalJSON( data );
            /// @todo we should check that either result or error are null...
            // we return in responseJSON both the jsonrpc-style members and the ezjscore ones
            response.content = response.result;
            response.error_text = response.error;
            returnObject.responseJSON = response;
        } catch ( error ) {
            var c = _configBak;
            if ( c.error !== undefined )
            {
                returnObject.statusText = error.message + ' error in file ' + error.fileName + ' line ' + error.lineNumber;
                c.error( returnObject );
                return;
            }
            else
            {
                throw error;
            }
        }

        var c = _configBak;
        if ( c.success !== undefined )
        {
            c.success( returnObject );
        }
        else if ( window.console !== undefined )
        {
            if ( returnObject.responseJSON.error_text )
                window.console.error( 'Y.io.jsonrpc(): ' + returnObject.responseJSON.error_text );
            else
                window.console.log( 'Y.io.jsonrpc(): ' + returnObject.responseJSON.content );
        }
    }

    //_jsonrpc.url = _serverUrl;
    //$.jsonrpc = _jsonrpc;
})(jQuery);
//{/literal}