<h3>Test call to Flickr</h3>

{def $results = fetch('webservices', 'call',
                 hash('server', 'flickr',
                      'method', 'flickr.photos.licenses.getInfo',
                      'parameters', hash(
                        'api_key', 'youshouldgetonefromflickr...',
                        'format', 'json',
                        'nojsoncallback', 1)))}
{$results|attribute(show, 4)}