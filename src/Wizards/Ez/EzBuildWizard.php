<?php declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.10.11.
 * Time: 16:13
 */

namespace Wf\Webtown\Wizards\Ez;

use Wf\DockerWorkflowBundle\Environment\Commander;
use Wf\DockerWorkflowBundle\Environment\EzEnvironmentParser;
use Wf\DockerWorkflowBundle\Environment\IoManager;
use Wf\DockerWorkflowBundle\Environment\WfEnvironmentParser;
use Wf\DockerWorkflowBundle\Event\Wizard\BuildWizardEvent;
use Wf\DockerWorkflowBundle\Exception\CommanderRunException;
use Wf\DockerWorkflowBundle\Wizard\WizardInterface;
use Wf\DockerWorkflowBundle\Wizards\BaseWizard;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EzBuildWizard extends BaseWizard implements WizardInterface
{
    protected $askDirectory = true;

    /**
     * @var WfEnvironmentParser
     */
    protected $wfEnvironmentParser;

    /**
     * @var EzEnvironmentParser
     */
    protected $ezEnvironmentParser;

    public function __construct(
        WfEnvironmentParser $wfEnvironmentParser,
        EzEnvironmentParser $ezEnvironmentParser,
        IoManager $ioManager,
        Commander $commander,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->wfEnvironmentParser = $wfEnvironmentParser;
        $this->ezEnvironmentParser = $ezEnvironmentParser;
        parent::__construct($ioManager, $commander, $eventDispatcher);
    }

    public function getDefaultName(): string
    {
        return 'eZ Project Builder';
    }

    public function getInfo(): string
    {
        return 'Create an eZ project';
    }

    public function getDefaultGroup(): string
    {
        return 'Builder';
    }

    /**
     * @param BuildWizardEvent $event
     *
     * @throws CommanderRunException
     */
    public function build(BuildWizardEvent $event): void
    {
        $directoryQuestion = new Question('Installation directory: ', '.');
        $directory = $this->askDirectory
            ? $this->ask($directoryQuestion)
            : '.';
        $targetProjectDirectory = $event->getWorkingDirectory() . \DIRECTORY_SEPARATOR . $directory;
        $event->setWorkingDirectory($targetProjectDirectory);

        $config = [
            'ezsystems/ezplatform-ee' => 'studio-clean',
            'ezsystems/ezplatform-ee-demo' => 'demo',
            'ezsystems/ezplatform' => 'clean',
            'ezsystems/ezplatform-demo' => 'platform-demo',
        ];

        $packageQuestion = new ChoiceQuestion(
            'Which package do you want to build? [<info>ezsystems/ezplatform-ee</info>]',
            array_keys($config),
            0
        );
        $package = $this->ask($packageQuestion);
        $ez_install_type = $config[$package];

        $composerRequired = [];
        $requireKaliopMigrationQuestion = new ConfirmationQuestion('Do you want to install <info>kaliop/ezmigrationbundle</info>?', true, '/^[yi]/i');
        $requireKaliopMigration = $this->ask($requireKaliopMigrationQuestion);
        if ($requireKaliopMigration) {
            $composerRequired[] = 'kaliop/ezmigrationbundle';
        }
        $requireDoctrineOrmQuestion = new ConfirmationQuestion('Do you need <info>doctrine migration</info>?', true, '/^[yi]/i');
        $requireDoctrineMigrations = $this->ask($requireDoctrineOrmQuestion);
        if ($requireDoctrineMigrations) {
            $composerRequired[] = 'doctrine/doctrine-migrations-bundle';
        }

        $this->commander->run(sprintf('mkdir -p %s', $targetProjectDirectory));
        $this->commander->cd($targetProjectDirectory);
        $this->runCmdInContainer(sprintf('composer create-project %s .', $package));
        if (!file_exists($targetProjectDirectory . '/.git')) {
            $this->commander->run('git init');
        }
        $this->commander->run('git add . && git commit -m "Init"');

        if ('ezsystems/ezplatform' != $package) {
            $this->createAuthJson($targetProjectDirectory);
        }

        if (\count($composerRequired) > 0) {
            $this->runCmdInContainer(sprintf('composer require %s', implode(' ', $composerRequired)));
            $this->commander->run('git add . && git commit -m "Add some composer package"');
        }

        if ($requireKaliopMigration) {
            $this->ioManager->writeln('<info>Please register the <comment>kaliop migration bundle</comment> in the <comment>AppKernel.php</comment> file</info>');
        }
        if ($requireDoctrineMigrations) {
            $this->ioManager->writeln('<info>Please register the <comment>doctrine migration bundle</comment> in the <comment>AppKernel.php</comment> file</info>');
        }
    }

    protected function createAuthJson($targetProjectDirectory): void
    {
        $this->ioManager->writeln('');
        $usernameQuestion = new Question('<comment>Username</comment> for <info>updates.ez.no</info> repository: ');
        $auth_username = $this->ask($usernameQuestion);
        $passwordQuestion = new Question('<comment>Password</comment>: ');
        $auth_password = $this->ask($passwordQuestion);

        $tpl = <<<EOL
{
    "http-basic": {
        "updates.ez.no": {
            "username": "$auth_username",
            "password": "$auth_password"
         }
    }
}
EOL;

        file_put_contents($targetProjectDirectory . '/auth.json', $tpl);
        $this->ioManager->writeln('The <info>auth.json</info> is created');
    }

    public function isBuilt(string $targetProjectDirectory): bool
    {
        return $this->ezEnvironmentParser->isEzProject($targetProjectDirectory)
            || $this->wfEnvironmentParser->wfIsInitialized($targetProjectDirectory);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDockerImage(): string
    {
        return 'fchris82/symfony:ez2';
    }
}
