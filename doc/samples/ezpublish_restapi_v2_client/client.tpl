<h3>Test call to eZ Publih REST API v2</h3>

(full docs at: https://github.com/ezsystems/ezpublish-kernel/blob/master/doc/specifications/rest/REST-API-V2.rst)

{def $results = fetch('webservices', 'call',
                 hash('server', 'ezprestapiv2',
                      'method', '/content/objectstategroups'))
     $res2 = array()}
{foreach $results.ObjectStateGroupList.ObjectStateGroup as $group}
   <h4>Group {$group.id}, identifier: {$group.identifier|wash()}</h4>

    {set $res2 = fetch('webservices', 'call',
                  hash('server', 'ezprestapiv2',
                       'method', concat('/content/objectstategroups/', $group.id, '/objectstates'))
    }
    <ul>
        {foreach $res2.ObjectStateList.ObjectState as $state}
            <li>State: {$state.id}, identifier: {$state.identifier|wash()}</li>
        {/foreach}
    </ul>

{/foreach}


{* and an update done in templates... BRRR *}

{set $res2 = fetch('webservices', 'call',
              hash('server', 'ezprestapiv2',
                   'method', concat('/content/objectstategroups/', $group.id, '/objectstates/', $state.id),
                   'parameters', hash(
                       'ObjectState', hash(
                           'identifier', 'not_quite_unlocked'
                   )),
                   'options', hash(
                       'requestType', 'application/vnd.ez.api.ObjectStateUpdate+json',
                       'method', 'PATCH')))
}
{if isset( $res2.ObjectState )}
    OK
{else}
    KO
{/if}