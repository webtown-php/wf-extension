<?php
namespace Deployer;

function sf($cmd, $options = '', $runOptions = [])
{
    return run(buildSfCommand($cmd, $options), $runOptions);
}

function buildSfCommand($cmd, $options = '')
{
    return sprintf('{{ "{{bin/console}}" }} %s {{ "{{console_options}}" }} %s', $cmd, $options);
}

/**
 * Az env változókat elérhetővé teszi a helyőrzőkben, `env.` prefixszel: `USER` --> `{{ "{{env.USER}}" }}`
 */
function loadEnvironments()
{
    foreach ($_ENV as $key => $value) {
        set('env.' . $key, $value);
    }
}
