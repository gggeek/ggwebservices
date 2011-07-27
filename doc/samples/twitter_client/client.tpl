<h3>Test call to Twitter: last messages</h3>
(full docs at: https://dev.twitter.com/)

{def $results = fetch('webservices', 'call',
                 hash('server', 'twitter',
                      'method', 'statuses/public_timeline.json',
                      'parameters', hash()))}
<ul>
{foreach $results as $tweet}
    <li>From "{$tweet.user.name|wash()}": {$tweet.text|wash()}</a></li>
{/foreach}
</ul>

