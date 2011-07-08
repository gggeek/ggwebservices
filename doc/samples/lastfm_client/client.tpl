<h3>LastFM client test</h3>

{def $results = fetch('webservices', 'call',
                 hash('server', 'lastfm',
                      'method', 'artist.getInfo',
                      'parameters', hash(
                        'artist', 'Toto Cutugno',
                        'api_key', 'youshouldgetonefromlastfm...',
                        'format', 'json')))}
{$results|attribute(show, 3)}
