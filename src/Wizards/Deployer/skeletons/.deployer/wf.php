<?php
namespace Deployer;

// @todo (Chris) A deploy folyamán még vannak hibák. Ha új, üres könyvtárba kell létrehozni a projektet, új fájlokkal, akkor a shared files-ok üresek maradnak, és a wf init a másolással nem hozza létre a megfelelő fájlokat.
$wfCommands = [
    'init',
    'install',
    'reinstall',
    'up',
    'down',
    'restart'
];

set('wf', function() {
    $path = rtrim(get('wf_bin_path', '~/bin'), '/');
    return $path . '/wf';
});

set('wizard', function() {
    $path = rtrim(get('wf_bin_path', '~/bin'), '/');
    return $path . '/wizard';
});

foreach ($wfCommands as $command) {
    task('wf:' . $command, function () use ($command) {
        if (has('current_path') && test('[ -d {{ "{{current_path}}" }} ]')) {
            run('cd {{ "{{current_path}}" }} && {{ "{{wf}}" }} ' . $command);
        }
    })
        ->desc(sprintf('Workflow command: `{{ "{{wf}}" }} %s`', $command))
        ->onRoles('workflow')
    ;
}
before('deploy:unlock', 'wf:up');


task('wf:down:previous', function () {
    if (has('previous_release')) {
        run('cd {{ "{{previous_release}}" }} && {{ "{{wf}}" }} down');
    }
})
    ->desc('Workflow command: `{{ "{{wf}}" }} down` in the previous release!')
    ->onRoles('workflow');
after('deploy:release', 'wf:down:previous');
