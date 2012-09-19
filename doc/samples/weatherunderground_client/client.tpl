<h3>Test calls to Weather Underground</h3>
(full docs at: http://www.wunderground.com/weather/api/d/docs)

<b>Geolocation service: Oslo</b>
{def $results = fetch('webservices', 'call',
                 hash('server', 'wunderground',
                      'method', 'geolookup/q/Norway/Oslo.json',
                      'parameters', hash()))}
<p>Timezone: {$results.response.location.tz_long|wash()}</p>
<p>Latitude: {$results.response.location.lat|wash()}</p>
<p>Longitude: {$results.response.location.lon|wash()}</p>*}
{*$results|attribute(show)*}

<hr/>
<b>Weather forecast for the 3 upcoming days: Olso</b>
{set $results = fetch('webservices', 'call',
                 hash('server', 'wunderground',
                      'method', 'forecast/q/Norway/Oslo.json',
                      'parameters', hash()))}
{foreach $results.forecast.simpleforecast.forecastday as $day max 3}
    <div style="float: left;">
        <img src="{$day.icon_url|wash}" alt="{$day.conditions|wash}"/>
        {$day.low.celsius|wash} - {$day.high.celsius|wash}C<br/>
        {$day.conditions|wash}
    </div>
{/foreach}
{*$results.forecast|attribute(show)*}
