<?php declare(strict_types=1);

namespace Wf\Webtown\Recipes\GitlabCiWebtownRunner;

use Wf\Webtown\Recipes\GitlabCi\GitlabCiRecipe;
use Wf\DockerWorkflowBundle\Exception\SkipSkeletonFileException;
use Wf\DockerWorkflowBundle\Skeleton\FileType\SkeletonFile;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Finder\SplFileInfo;

class GitlabCiWebtownRunnerRecipe extends GitlabCiRecipe
{
    const NAME = 'gitlab_ci_webtown_runner';

    public function getName(): string
    {
        return static::NAME;
    }

    public static function getSkeletonParents(): array
    {
        return [GitlabCiRecipe::class];
    }

    public function getConfig(): NodeDefinition
    {
        $rootNode = parent::getConfig();

        /**
         *  gitlab_ci_webtown_runner:
         *      share_home_with: engine
         *      volumes:
         *          mysql:
         *              data: /var/lib/mysql
         */
        $rootNode
            ->info('<comment>GitLab CI Webtown Runner</comment>')
            ->children()
                ->arrayNode('share_home_with')
                    ->info('<comment>Share composer cache or other things between tests. Service name list.</comment>')
                    ->scalarPrototype()->end()
                    ->defaultValue(['engine'])
                ->end()
                ->arrayNode('volumes')
                    ->info('<comment>Register template volumes.</comment>')
                    ->useAttributeAsKey('service')
                    ->variablePrototype()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) {
                                return ['data' => $v];
                            })
                            ->end()
                        ->end()
                        ->validate()
                            ->always(function ($v) {
                                foreach ($v as $name => $target) {
                                    if (!\is_string($name)) {
                                        throw new InvalidConfigurationException(sprintf('You have to use string key in `%s`.volumes', static::NAME));
                                    }
                                }

                                return $v;
                            })
                        ->end()
                        ->example([
                            'service1' => '/usr/mysql/data',
                            'service2' => ['data' => '/usr/mysql/data', 'config' => '/usr/mysql/config'],
                        ])
                    ->end()
                ->end()
            ->end()
        ;

        return $rootNode;
    }

    public function getSkeletonVars(string $projectPath, array $recipeConfig, array $globalConfig): array
    {
        $baseVars = parent::getSkeletonVars($projectPath, $recipeConfig, $globalConfig);

        $output = [];
        exec(sprintf('cd %s && git rev-parse --short HEAD', $projectPath), $output);

        return array_merge($baseVars, [
            'git_hash' => trim(implode('', $output)),
        ]);
    }

    /**
     * @param SplFileInfo $fileInfo
     * @param $recipeConfig
     *
     * @throws SkipSkeletonFileException
     *
     * @return SkeletonFile
     */
    protected function buildSkeletonFile(SplFileInfo $fileInfo, array $recipeConfig): SkeletonFile
    {
        switch ($fileInfo->getFilename()) {
            case 'docker-compose.home.yml':
                if (0 === \count($recipeConfig['share_home_with'] ?? [])) {
                    throw new SkipSkeletonFileException();
                }
                break;
            case 'docker-compose.volumes.yml':
                if (0 === \count($recipeConfig['volumes'] ?? [])) {
                    throw new SkipSkeletonFileException();
                }
                break;
        }

        return parent::buildSkeletonFile($fileInfo, $recipeConfig);
    }
}
