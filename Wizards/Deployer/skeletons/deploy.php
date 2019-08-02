<?php
namespace Deployer;

const ROLE_DEFAULT = 'default';
const ROLE_WORKFLOW = 'workflow';
const ROLE_BUILD = 'build';
const ROLE_FIXTURE_RELOAD = 'fixtures';

require 'vendor/deployer/deployer/recipe/common.php';
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

// You can set '--full' eg --> wf install --full
set('wf_install_param', '');

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

task('database:reload:wf', function() {
    cd('{{ "{{release_path}}" }}');
    run('{{ "{{wf}}" }} dbreload --full');
})
    ->desc('Load the fixtures')
    ->onRoles([ROLE_WORKFLOW, ROLE_FIXTURE_RELOAD])
;
after('deploy:wf', 'database:reload:wf');

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
