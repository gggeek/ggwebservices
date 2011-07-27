<h3>Test call to YQL: yahoo.finance stock quotes</h3>
(full docs at: http://developer.yahoo.com/yql/)

{def $results = fetch('webservices', 'call',
                 hash('server', 'yql',
                      'method', 'select * from yahoo.finance.quotes where symbol in ("MSFT", "GOOG")',
                      'parameters', hash(
                        'env', 'http://datatables.org/alltables.env',
                        'format', 'json')))}
<ul>
{*$results|attribute(show,4)*}
{foreach $results.query.results.quote as $symbol}
    <li>"{$symbol.symbol|wash()}": {$symbol.Ask|wash()}</a></li>
{/foreach}
</ul>
