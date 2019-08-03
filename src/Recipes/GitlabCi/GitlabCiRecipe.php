<?php declare(strict_types=1);

namespace Wf\Webtown\Recipes\GitlabCi;

use Wf\DockerWorkflowBundle\Recipes\BaseRecipe;

class GitlabCiRecipe extends BaseRecipe
{
    const NAME = 'gitlab_ci';

    public function getName(): string
    {
        return static::NAME;
    }
}
