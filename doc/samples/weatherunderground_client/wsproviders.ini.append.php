<?php /*

[wunderground]
providerType=REST
# nb: you will have to sign up for an API key and substitute it here.
# Free api usage is possible with limited call rates.
providerUri=http://api.wunderground.com/api/<Your_Key>/
Options[]
# Note: Weather Underground sends json with text/plain content-type for error messages.
# If we uncomment this, it means that those (bad) responses will be parsed as succesful
# json calls and further error checking done within the "sucess" callback handler
#Options[responseType]=application/json

*/