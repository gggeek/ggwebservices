<h3>Test call to SOLR: serach for 'conference'</h3>
(full docs at: http://wiki.apache.org/solr/QueryParametersIndex)

{def $results = fetch('webservices', 'call',
                 hash('server', 'solr',
                      'method', 'select',
                      'parameters', hash(
                        'q', 'conference',
                        'wt', 'json')))}
<ul>
{foreach $results.response.docs as $result}
    <li><a href={$result.meta_url_alias_s|ezurl()}>{$result.meta_name_t|wash()}</a></li>
{/foreach}
</ul>
