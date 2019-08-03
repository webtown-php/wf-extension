<?php declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2018.11.12.
 * Time: 15:55
 */

namespace Wf\Webtown\Wizards\DeployerSfGe4;

use Wf\DockerWorkflowBundle\Exception\WizardSomethingIsRequiredException;
use Wf\DockerWorkflowBundle\Exception\WizardWfIsRequiredException;
use Wf\Webtown\Wizards\DeployerSfLe3\DeployerWizard as BaseDeployerWizard;

class DeployerWizard extends BaseDeployerWizard
{
    public function getDefaultName(): string
    {
        return 'Deployer (SF >= 4)';
    }

    public function getInfo(): string
    {
        return 'Add Deployer for a Symfony project (SF >= 4)';
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

        $this->checkSfVersion($targetProjectDirectory, 4, '>=');

        return true;
    }
}
