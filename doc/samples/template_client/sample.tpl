{* sample webservice calls *}

{def $method = 'system.listMethods'
     $server = 'phpxmlrpc'
     $params = array()
     $servertype = ezini( $server, 'providerType', 'wsproviders.ini' )}
Calling method "{$method}" on {$servertype} server "{$server}"
{def $test = fetch('webservices', 'call', hash(
                    'server', $server,
                    'method', $method ) )}
Obtained:
{$test|attribute(show)}

{set $method = 'echoInteger'
     $server = 'mssoapinterop'
     $params = array( 123 )
     $servertype = ezini( $server, 'providerType', 'wsproviders.ini' )}
Calling method "{$method}" on {$servertype} server "{$server}"
{set $test = fetch('webservices', 'call', hash(
                    'server', $server,
                    'method', $method,
                    'parameters', $params ) )}
Obtained:
{$test}
