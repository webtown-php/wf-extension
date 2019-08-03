<?php
namespace Deployer;

/**
 * This task remove (stop) the deployed environment. DELETE all files and data!
 */

const ROLE_REMOVABLE = 'removable';

task('deploy:remove', [
    'deploy:remove:stop',
    'deploy:remove:stop',
    'deploy:remove:delete',
    'deploy:remove:success',
])
    ->desc('Remove deployed WF branch. DELETE all files and data!!!')
    ->onRoles(ROLE_REMOVABLE)
;

task('deploy:remove:stop', function() {
    cd('{{ "{{current_path}}" }}');
    run('{{ "{{wf}}" }} down');
})->setPrivate();

task('deploy:remove:delete', function() {
    run('rm -rf {{ "{{deploy_path}}" }}');
})->setPrivate();


task('deploy:remove:success', function () {
    writeln('<info>Successfully removed!</info>');
})
    ->local()
    ->shallow()
    ->setPrivate();
