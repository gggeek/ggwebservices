/**
* JSON-RPC client for jquery
* API based on ezjscore's client for maximum interoperability (but not identical!)
* Usese jquery.json plugin for json (de)serializing
* Works both if parsed as javascript-generating template or if included as plain javascript file
*
* @author G. Giunta
* @copyright (c) 2009 G. Giunta
* @version $Id$
*
* @todo use closures instead of saving stuff around for later
*/
//{literal}
(function($) {

    // this looks weird but is ok, it is just the result of writing meta-js via tpl
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

    $.jsonrpc = function _jsonrpc( callMethod, callParams, callBack )
    {
        var url = _serverUrl + '/webservices/execute/jsonrpc';

        // encode function arguments as post params
        post = $.toJSON({method: callMethod, params: callParams, id: 1});

        // force json transport

        // backup user success call, as we inject our decoding success call
        if ( callBack !== undefined )
            _configBak = callBack;

        c = {
            contentType: 'application/json',
            data: post,
            dataType: 'text', // avoid having jquery parsing response json using eval
            accepts: { text: 'application/json,text/javascript' }, // fix the accept header
            processData: false,
            success: _iojsonrpcSuccess,
            type: 'POST',
            url: url
        };
        return $.ajax( c );
    };

    function _iojsonrpcSuccess( data, textStatus )
    {
    }

    //_jsonrpc.url = _serverUrl;
    //$.jsonrpc = _jsonrpc;
})(jQuery);
//{/literal}