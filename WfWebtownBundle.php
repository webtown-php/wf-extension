<?php declare(strict_types=1);


namespace Wf\Webtown;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Wf\DockerWorkflowBundle\DependencyInjection\Compiler\CollectRecipesPass;
use Wf\DockerWorkflowBundle\DependencyInjection\Compiler\CollectWizardsPass;
use Wf\DockerWorkflowBundle\Recipes\BaseRecipe;
use Wf\DockerWorkflowBundle\WfDockerWorkflowBundle;
use Wf\DockerWorkflowBundle\Wizard\WizardInterface;

class WfWebtownBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        // Register autoconfigurations
        $container->registerForAutoconfiguration(BaseRecipe::class)
            ->addTag(WfDockerWorkflowBundle::RECIPE_TAG);
        $container->registerForAutoconfiguration(WizardInterface::class)
            ->addTag(WfDockerWorkflowBundle::WIZARD_TAG);

        // Register collect passes
        $container->addCompilerPass(new CollectRecipesPass());
        $container->addCompilerPass(new CollectWizardsPass());
    }
}
