<?php /*

[solr]
providerType=REST
providerUri=http://localhost:8983/solr
Options[]
# Solr/Jetty does not set proper content-type headers based upon the ResponseWriter we ask, so we force it here
# NB: do not forget to add a wt=json param in your requests
Options[responseType]=application/json

*/