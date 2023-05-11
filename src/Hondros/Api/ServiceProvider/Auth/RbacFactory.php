<?php

namespace Hondros\Api\ServiceProvider\Auth;

use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Permissions\Rbac\Rbac as LaminasRbac;
use Laminas\Permissions\Rbac\Role;
use Hondros\Api\Model\Entity;

class RbacFactory implements FactoryInterface
{
    /**
     * @todo add content role to db - allow multiple roles?
     * @param ServiceLocatorInterface $serviceLocator
     * @return LaminasRbac
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // setup class and roles
        $rbac = new LaminasRbac();
        $adminRole  = new Role(Entity\User::ROLE_ADMIN);
        $contentRole = new Role(Entity\User::ROLE_CONTENT);
        $memberRole  = new Role(Entity\User::ROLE_MEMBER);
        $guestRole  = new Role(Entity\User::ROLE_GUEST);

        // inherit
        $adminRole->addChild($memberRole);
        $adminRole->addChild($contentRole);
        $memberRole->addChild($guestRole);

        /**
         * admin
         */
        $adminRole->addPermission('GET.ENROLLMENTS');
        $adminRole->addPermission('POST.ENROLLMENTS.ENABLE');
        $adminRole->addPermission('POST.ENROLLMENTS.DISABLE');
        $adminRole->addPermission('POST.ENROLLMENTS.EXTEND');
        $adminRole->addPermission('GET.USERS');
        $adminRole->addPermission('POST.USERS');
        $adminRole->addPermission('POST.USERS.RESET_TOKEN');
        $adminRole->addPermission('POST.USERS.ENABLE');
        $adminRole->addPermission('POST.USERS.DISABLE');
        $adminRole->addPermission('POST.EXAMS.IMPORT');
        $adminRole->addPermission('POST.MODULES.IMPORT');
        $adminRole->addPermission('PUT.MODULES');
        $adminRole->addPermission('PUT.EXAMS');
        $adminRole->addPermission('DELETE.EXAMS.MODULES');
        $adminRole->addPermission('POST.EXAMS.MODULES');

        /**
         * content
         */
        $contentRole->addPermission('POST.QUESTIONS');
        $contentRole->addPermission('PUT.QUESTIONS');
        $contentRole->addPermission('GET.QUESTIONS.AUDITS');
        $contentRole->addPermission('GET.QUESTIONS.ANSWERS.AUDITS');
        $contentRole->addPermission('POST.QUESTIONS.SEARCH');
        $contentRole->addPermission('PUT.ANSWERS');
        $contentRole->addPermission('PUT.EXAMS.MODULES');
        $contentRole->addPermission('POST.EXAMS.EXPORT');
        $contentRole->addPermission('GET.MODULE_QUESTIONS');
        $contentRole->addPermission('GET.STATES');
        $contentRole->addPermission('GET.INDUSTRIES');
        $contentRole->addPermission('GET.REPORT.QUESTION.STATS');
        $contentRole->addPermission('GET.REPORT.APPLICATION.STATS');

        // finish setting up
        $rbac->addRole($adminRole);
        $rbac->addRole($memberRole);
        $rbac->addRole($guestRole);

        return $rbac;
    }
}