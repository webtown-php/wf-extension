<?php declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2018.11.12.
 * Time: 15:55
 */

namespace Wf\Webtown\Wizards\DeployerSfLe3;

use Wf\WorkflowBundle\Environment\Commander;
use Wf\WorkflowBundle\Environment\EnvParser;
use Wf\WorkflowBundle\Environment\EzEnvironmentParser;
use Wf\WorkflowBundle\Environment\IoManager;
use Wf\WorkflowBundle\Environment\MicroParser\ComposerInstalledVersionParser;
use Wf\WorkflowBundle\Environment\WfEnvironmentParser;
use Wf\WorkflowBundle\Event\Wizard\BuildWizardEvent;
use Wf\WorkflowBundle\Exception\WizardSomethingIsRequiredException;
use Wf\WorkflowBundle\Exception\WizardWfIsRequiredException;
use Wf\Webtown\Wizards\Deployer\DeployerWizard as BaseDeployerWizard;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class DeployerWizard extends BaseDeployerWizard
{
    /**
     * @var EzEnvironmentParser
     */
    protected $ezEnvironmentParser;

    public function __construct(
        ComposerInstalledVersionParser $composerInstalledVersionParser,
        WfEnvironmentParser $wfEnvironmentParser,
        EzEnvironmentParser $ezEnvironmentParser,
        EnvParser $envParser,
        IoManager $ioManager,
        Commander $commander,
        EventDispatcherInterface $eventDispatcher,
        Environment $twig,
        Filesystem $filesystem
    ) {
        parent::__construct(
            $composerInstalledVersionParser,
            $wfEnvironmentParser,
            $envParser,
            $ioManager,
            $commander,
            $eventDispatcher,
            $twig,
            $filesystem
        );
        $this->ezEnvironmentParser = $ezEnvironmentParser;
    }

    public function getDefaultName(): string
    {
        return 'Deployer (SF <= 3)';
    }

    public function getInfo(): string
    {
        return 'Add Deployer for a Symfony project (SF <= 3)';
    }

    /**
     * @param $targetProjectDirectory
     *
     * @throws WizardSomethingIsRequiredException
     * @throws WizardWfIsRequiredException
     *
     * @return bool
     */
    public function checkRequires(string $targetProjectDirectory): bool
    {
        parent::checkRequires($targetProjectDirectory);

        $this->checkSfVersion($targetProjectDirectory, 4, '<');

        return true;
    }

    /**
     * @param $targetProjectDirectory
     * @param $version
     * @param $operator
     *
     * @throws WizardSomethingIsRequiredException
     */
    protected function checkSfVersion(string $targetProjectDirectory, string $version, string $operator): void
    {
        $sfVersion = $this->ezEnvironmentParser->getSymfonyVersion($targetProjectDirectory);
        if ($sfVersion && !version_compare($sfVersion, $version, $operator)) {
            throw new WizardSomethingIsRequiredException(sprintf(
                'The required Symfony version is: %s%s. Your current SF version is: %s',
                $operator,
                $version,
                $sfVersion ?: 'not installed/unknown'
            ));
        }
    }

    protected function readSkeletonVars(BuildWizardEvent $event): array
    {
        $targetProjectDirectory = $event->getWorkingDirectory();

        $variables = parent::readSkeletonVars($event);
        $sfVariables = $this->ezEnvironmentParser->getSymfonyEnvironmentVariables($targetProjectDirectory);
        $variables = array_merge($variables, $sfVariables);

        return $variables;
    }

    public static function getSkeletonParents(): array
    {
        return [BaseDeployerWizard::class];
    }
}
