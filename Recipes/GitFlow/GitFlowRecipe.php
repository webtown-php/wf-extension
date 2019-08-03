<?php declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2018.03.14.
 * Time: 21:55
 */

namespace Wf\Webtown\Recipes\GitFlow;

use Wf\DockerWorkflowBundle\Recipes\BaseRecipe;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * Class Recipe
 *
 * Extends the base functions with the gitflow commands.
 */
class GitFlowRecipe extends BaseRecipe
{
    const NAME = 'git_flow';

    public function getName(): string
    {
        return static::NAME;
    }

    public function getConfig(): NodeDefinition
    {
        $rootNode = parent::getConfig();

        /**
         *  git_flow:
         *      develop: dev
         *      feature: f
         *      hotfix:  h
         */
        $rootNode
            ->info('<comment>GitFlow</comment>')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('develop')
                    ->info('<comment>Develop branch name</comment>')
                    ->defaultValue('develop')
                ->end()
                ->scalarNode('feature')
                    ->info('<comment>Feature branches prefix</comment>')
                    ->defaultValue('feature')
                ->end()
                ->scalarNode('hotfix')
                    ->info('<comment>Hotfix branches prefix</comment>')
                    ->defaultValue('hotfix')
                ->end()
            ->end()
        ;

        return $rootNode;
    }
}
