<?php declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2018.11.12.
 * Time: 15:45
 */

namespace Wf\Webtown\Wizards\GitlabCIProject;

use Wf\DockerWorkflowBundle\Environment\Commander;
use Wf\DockerWorkflowBundle\Environment\EnvParser;
use Wf\DockerWorkflowBundle\Environment\EzEnvironmentParser;
use Wf\DockerWorkflowBundle\Environment\IoManager;
use Wf\DockerWorkflowBundle\Environment\WfEnvironmentParser;
use Wf\DockerWorkflowBundle\Event\Wizard\BuildWizardEvent;
use Wf\DockerWorkflowBundle\Exception\WizardSomethingIsRequiredException;
use Wf\DockerWorkflowBundle\Exception\WizardWfIsRequiredException;
use Wf\DockerWorkflowBundle\Wizards\BaseSkeletonWizard;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

class GitlabCIProjectWizard extends BaseSkeletonWizard
{
    /**
     * @var EzEnvironmentParser
     */
    protected $ezEnvironmentParser;

    /**
     * @var WfEnvironmentParser
     */
    protected $wfEnvironmentParser;

    /**
     * @var EnvParser
     */
    protected $envParser;

    /**
     * GitlabCIProjectWizard constructor.
     *
     * @param EzEnvironmentParser      $ezEnvironmentParser
     * @param WfEnvironmentParser      $wfEnvironmentParser
     * @param EnvParser                $envParser
     * @param IoManager                $ioManager
     * @param Commander                $commander
     * @param EventDispatcherInterface $eventDispatcher
     * @param Environment              $twig
     * @param Filesystem               $filesystem
     */
    public function __construct(
        EzEnvironmentParser $ezEnvironmentParser,
        WfEnvironmentParser $wfEnvironmentParser,
        EnvParser $envParser,
        IoManager $ioManager,
        Commander $commander,
        EventDispatcherInterface $eventDispatcher,
        Environment $twig,
        Filesystem $filesystem
    ) {
        parent::__construct($ioManager, $commander, $eventDispatcher, $twig, $filesystem);
        $this->ezEnvironmentParser = $ezEnvironmentParser;
        $this->wfEnvironmentParser = $wfEnvironmentParser;
        $this->envParser = $envParser;
    }

    public function getDefaultName(): string
    {
        return 'GitlabCI';
    }

    public function getInfo(): string
    {
        return 'Initialize projet to Gitlab CI';
    }

    public function getDefaultGroup(): string
    {
        return 'Composer';
    }

    protected function readSkeletonVars(BuildWizardEvent $event): array
    {
        $targetProjectDirectory = $event->getWorkingDirectory();
        $variables = $this->ezEnvironmentParser->getSymfonyEnvironmentVariables($targetProjectDirectory);
        $wfConfiguration = $this->wfEnvironmentParser->getWorkflowConfiguration($targetProjectDirectory);
        $symfonyRecipeName = null;
        foreach ($wfConfiguration['recipes'] as $recipeName => $recipeConfig) {
            if (0 === strpos($recipeName, 'symfony')) {
                $symfonyRecipeName = $recipeName;
                break;
            }
        }

        return array_merge($variables, [
            'project_name' => basename($this->envParser->get('ORIGINAL_PWD', $targetProjectDirectory)),
            'sf_recipe_name' => $symfonyRecipeName,
        ]);
    }

    protected function getBuiltCheckFile(): string
    {
        return '.gitlab-ci.yml';
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
        if (!file_exists($targetProjectDirectory . '/composer.json')) {
            throw new WizardSomethingIsRequiredException(sprintf('Initialized composer is required for this!'));
        }
        if (!$this->wfEnvironmentParser->wfIsInitialized($targetProjectDirectory)) {
            throw new WizardWfIsRequiredException($this, $targetProjectDirectory);
        }

        return parent::checkRequires($targetProjectDirectory);
    }

    /**
     * @param BuildWizardEvent $event
     *
     * @return string
     */
    public function build(BuildWizardEvent $event): void
    {
        $workingDirectory = $event->getWorkingDirectory();
        // Ha létezik parameters.yml, akkor annak is létrehozunk egy gitlab verziót
        if ($this->fileSystem->exists($workingDirectory . '/app/config/parameters.yml.dist')) {
            $this->fileSystem->copy(
                $workingDirectory . '/app/config/parameters.yml.dist',
                $workingDirectory . '/app/config/parameters.gitlab-ci.yml'
            );
            $this->ioManager->writeln(sprintf(
                '<info> ✓ The </info>%s/<comment>%s</comment><info> file has been created or modified.</info>',
                'app/config',
                'parameters.gitlab-ci.yml'
            ));
        }
    }
}
