<?php declare(strict_types=1);

namespace Wf\Webtown\Recipes\PhpDeployerEnvironment;

use Wf\DockerWorkflowBundle\Recipes\BaseRecipe as BaseRecipe;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class PhpDeployerEnvironmentRecipe extends BaseRecipe
{
    const NAME = 'php_deployer_environment';

    public function getName(): string
    {
        return static::NAME;
    }

    public function getConfig(): NodeDefinition
    {
        $rootNode = parent::getConfig();

        /**
         *  php_deployer_environment:
         *      share: engine
         */
        $rootNode
            ->info('<comment>PHP Deployer environment</comment>')
            ->children()
                ->arrayNode('share')
                    ->info('<comment>Share deployer share directory. Service name list.</comment>')
                    ->scalarPrototype()->end()
                    ->defaultValue(['engine'])
                ->end()
            ->end()
        ;

        return $rootNode;
    }
}
