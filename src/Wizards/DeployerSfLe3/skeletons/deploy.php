<?php
namespace Deployer;

const ROLE_DEFAULT = 'default';
const ROLE_WORKFLOW = 'workflow';
const ROLE_BUILD = 'build';
const ROLE_FIXTURE_RELOAD = 'fixtures';

require 'vendor/deployer/deployer/recipe/symfony{% if sf_version > 2 %}{{ sf_version }}{% endif %}.php';
require '.deployer/functions.php';
require '.deployer/DistFile.php';
require '.deployer/wf.php';
require '.deployer/deploy.remove.php';

// Project name
set('application', '{{ project_name | default('???') }}');

// Project repository
set('repository', '{{ remote_url | default('???') }}');

// [Optional] Allocate tty for git clone. Default value is false.
//set('git_tty', true);

// Timout beállítás
set('default_timeout', 600);

// Shared files/dirs between deploys
add('shared_files', [
    '.wf.yml',
]);
/**
 * Nem lehet symlink a Dockerfile! Valójában a `shared_files`-ben lenne a helye, de sajnos a docker nem tud azzal működni:
 * @see https://github.com/docker/compose/issues/5315
 */
//add('copy_dirs', [
//    '.docker/engine/Dockerfile',
//]);
add('shared_dirs', [
    'web/var',
    '.wf/.data',
#    'node_modules',
]);

// Writable dirs by web server
add('writable_dirs', [
    'web/var'
]);

// You can set '--full' eg --> wf install --full
set('wf_install_param', '');
{# Missing, but used options in symfony4.php recipe #}

loadEnvironments();
inventory(__DIR__ . '/.deployer/hosts.yml');

// ============================== W O R K F L O W ================================
task('deploy:init-config', function () {
    cd('{{ "{{release_path}}" }}');
    foreach (get('dist_files', []) as $distFile => $targetFiles) {
        if (!is_array($targetFiles)) {
            $targetFiles = [$targetFiles];
        }
        foreach ($targetFiles as $targetFile) {
            $dist = new DistFile($distFile, $targetFile);
            if ($dist->isForce() || !test(sprintf('[[ -f %s ]]', $dist->getTargetFile()))) {
                if ($dist->isParseContent()) {
                    $tmpFile = sys_get_temp_dir() . '/' . md5($dist->getTargetFile());
                    $distContent = file_get_contents(__DIR__ . '/' . $dist->getDistFile());
                    file_put_contents($tmpFile, parse($distContent));
                    upload($tmpFile, '{{ "{{release_path}}" }}/' . $dist->getTargetFile());
                    writeln(file_get_contents($tmpFile));
                    run('cat {{ "{{release_path}}" }}/' . $dist->getTargetFile());
                    unlink($tmpFile);
                } else {
                    run(sprintf('cp -rf %s %s', $dist->getDistFile(), $dist->getTargetFile()));
                }
            }
        }
    }
});
before('deploy:shared', 'deploy:init-config');

task('deploy:wf', function () {
    cd('{{ "{{release_path}}" }}');
    writeln('Start init...');
    run('{{ "{{wf}}" }} init --only-prod');
    run('{{ "{{wf}}" }} restart');
    writeln('...Init is ready');
    writeln('Start install... (it would be long)');
    // A lassú futás miatt a timeout-ot megnöveljük
    run('{{ "{{wf}}" }} install {{ "{{wf_install_param}}" }}', [
        'timeout' => 1200,
    ]);
})->onRoles(ROLE_WORKFLOW);
after('deploy:shared', 'deploy:wf');

// ============================== D E F A U L T ================================
    // Drop/clean database
    task('database:fixture:drop-database', function () {
        sf('doctrine:database:drop', '--force --if-exists');
    })
        ->desc('Drop the database')
        ->onRoles([ROLE_DEFAULT, ROLE_FIXTURE_RELOAD]);
    // Migrate database before symlink new release.
    task('database:migrate', function () {
        sf('doctrine:database:create','--if-not-exists');
{% if is_ez %}
        // Csak akkor futtatjuk az ezplatform:install-t ha még nem létezik az adatbázis.
        run (sprintf(
            '%s || %s',
            buildSfCommand('doctrine:schema:validate', '--skip-mapping'),
            buildSfCommand('ezplatform:install', 'app')
        ));
{% endif %}
        sf('doctrine:migrations:migrate','--allow-no-migration');
{% if is_ez %}
        // A -u azért kell, hogy ne transaction-ben fusson, különben nem működnek a references dolgok
        sf('kaliop:migration:migrate','-u --default-language=hun-HU');
{% endif %}
    })
        ->desc('Build database.')
        ->onRoles(ROLE_DEFAULT);
    task('database:fixtures:load', function () {
        sf('doctrine:fixtures:load');
    })
        ->desc('Load fixtures.')
        ->onRoles([ROLE_DEFAULT, ROLE_FIXTURE_RELOAD]);
task('database:update', [
    'database:fixture:drop-database',
    'database:migrate',
    'database:fixtures:load'
])
    ->desc('Update the database')
    ->onRoles([ROLE_DEFAULT]);
before('deploy:symlink', 'database:update');

task('database:fixtures', function () {
    sf('doctrine:database:drop', '--force');
    sf('doctrine:database:create');
    sf('doctrine:migrations:migrate','--allow-no-migration');
    sf('doctrine:fixtures:load');
})
    ->desc('Load fixtures.')
    ->onRoles([ROLE_DEFAULT])
;

task('database:reload:wf', function() {
    cd('{{ "{{release_path}}" }}');
    run('{{ "{{wf}}" }} dbreload --full');
})
    ->desc('Load the fixtures')
    ->onRoles([ROLE_WORKFLOW, ROLE_FIXTURE_RELOAD])
;
after('deploy:wf', 'database:reload:wf');

// Only SF
$onlyDefaultTasks = [
    'deploy:assets',
    'deploy:assets:install',
    'deploy:assetic:dump',
    'deploy:vendors',
    'deploy:cache:clear',
    'deploy:cache:warmup',
    'database:migrate',
];
foreach ($onlyDefaultTasks as $task) {
    task($task)->onRoles(ROLE_DEFAULT);
}

// ============================== B U I L D ================================
task('deploy:build:files-clean', function() {
    cd('{{ "{{current_path}}" }}');
    // Írható könyvtárak törlése
    foreach (get('writable_dirs') as $dir) {
        run(sprintf('rm -rf %s/*', $dir));
    }
    // .gitignore adatok törlése
    $gitignoreFiles = [
        '.git',
        '.deployer',
        '.docker',
        'bin',
        'app/check.php',
        'app/SymfonyRequirements.php',
        'ide-twig.json',
    ];
    foreach ($gitignoreFiles as $file) {
        run(sprintf('rm -rf %s', $file));
    }

    // A paramters.yml ürítése
    run('echo "" > app/config/parameters.yml');
})
    ->desc('Törli a shared és writable fájlokat és könyvtárakat, amiket nem szeretnénk bezippelni.')
    ->setPrivate()
    ->onRoles(ROLE_BUILD);

task('deploy:build:add-composer', function() {
    $content = file_get_contents('https://getcomposer.org/composer.phar');
    $tmpFilePath = sys_get_temp_dir() . '/release_' . get('release_name') . '.composer.phar';
    file_put_contents($tmpFilePath, $content);
    upload($tmpFilePath, '{{ "{{current_path}}" }}/composer.phar');
    unlink($tmpFilePath);
});

task('deploy:build:update-version-number', function() {
    cd('{{ "{{current_path}}" }}');
    set('git_version_tag', function() {
        return runLocally('git describe --tags --always');
    });
    $yml = <<<EOS
parameters:
    app.version: {{ "{{git_version_tag}}" }}
EOS;
    set('version_yml_content', $yml);
    run('echo "{{ "{{version_yml_content}}" }}" > app/config/version_info.yml');
})
    ->desc('Frissíti a verzió számot.')
    ->setPrivate()
    ->onRoles(ROLE_BUILD);

task('deploy:build:create-zip', function() {
    cd('{{ "{{current_path}}" }}');
    run('tar -zchvf {{ "{{deploy_path}}" }}/{{ "{{release_name}}" }}.tar.gz .');
})
    ->desc('Zippeli a release-t.')
    ->onRoles(ROLE_BUILD);

task('deploy:build:create-makefile', function() {
    $content = file_get_contents(__DIR__ . '/.deployer/tpl/makefile');
    $configs = ['shared_dirs', 'shared_files', 'writable_dirs'];
    foreach ($configs as $configName) {
        $content = str_replace('%' . $configName . '%', implode(' ', get($configName, [])), $content);
    }
    $tmpFilePath = sys_get_temp_dir() . '/release_' . get('release_name') . '.makefile';
    file_put_contents($tmpFilePath, $content);
    upload($tmpFilePath, '{{ "{{deploy_path}}" }}/makefile');
    unlink($tmpFilePath);
})
    ->desc('Elkészíti a makefile-t a zip-hez.')
    ->setPrivate()
    ->onRoles(ROLE_BUILD);

task('deploy:build', [
    'deploy:build:files-clean',
    'deploy:build:update-version-number',
    'deploy:build:add-composer',
    'deploy:build:create-zip',
    'deploy:build:create-makefile',
])->onRoles(ROLE_BUILD);
after('cleanup', 'deploy:build');

// ============================== O T H E R ================================
// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

task('deploy:yarn', function() {
    cd('{{release_path}}');
    run('yarn -s');
    run('yarn run build');
})
    ->desc('Run yarn commands, build')
    ->onRoles(ROLE_DEFAULT);
after('deploy:vendors', 'deploy:yarn');
