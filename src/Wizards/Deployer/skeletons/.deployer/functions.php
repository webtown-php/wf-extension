<?php
namespace Deployer;

/**
 * Az env változókat elérhetővé teszi a helyőrzőkben, `env.` prefixszel: `USER` --> `{{ "{{env.USER}}" }}`
 */
function loadEnvironments()
{
    foreach ($_ENV as $key => $value) {
        set('env.' . $key, $value);
    }
}
