{*
  Sample webservice calls from template code

  For these to work, the corresponding servers have to be defined in settings file
  wsproviders.ini
*}

{def $server = 'phpxmlrpc'
     $method = 'system.listMethods'
     $params = array()}
Calling method "{$method}" on {ezini( $server, 'providerType', 'wsproviders.ini' )} server "{$server}"

{def $test = fetch( 'webservices', 'call', hash(
                        'server', $server,
                        'method', $method ) )}
Obtained:
{$test|attribute(show)}

{set $server = 'mssoapinterop'
     $method = 'echoInteger'
     $params = array( 123 )}
Calling method "{$method}" on {ezini( $server, 'providerType', 'wsproviders.ini' )} server "{$server}"

{set $test = fetch( 'webservices', 'call', hash(
                        'server', $server,
                        'method', $method,
                        'parameters', $params ) )}
Obtained:
{$test}
