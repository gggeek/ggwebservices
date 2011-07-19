<?php /*

[flickr]
providerType=REST
providerUri=http://api.flickr.com/services/rest/
Options[]
Options[nameVariable]=method
# flickr responds with text/plain content-type by default, so we force parsing as json
# (note that this entails putting the two parameters format=json and nojsoncallback=1 in the call)
Options[responseType]=application/json

*/ ?>