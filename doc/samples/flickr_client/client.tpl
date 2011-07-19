<h3>Test call to Flickr</h3>

(full docs at: https://dev.twitter.com/)

{def $results = fetch('webservices', 'call',
                 hash('server', 'flickr',
                      'method', 'flickr.photos.licenses.getInfo',
                      'parameters', hash(
                        'api_key', 'youshouldgetonefromflickr...',
                        'format', 'json',
                        'nojsoncallback', 1)))}
{foreach $results.licenses.license as $license}
   <a href="{$license.url|wash()}">{$license.name|wash()}</a>
{/foreach}
