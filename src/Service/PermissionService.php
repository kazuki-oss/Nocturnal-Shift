<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\PermissionRepository;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
    public function __construct(
        private Security $security,
        private PermissionRepository $permissionRepository
    ) {}

    public function can(string $permissionIdentifier, ?User $user = null): bool
    {
        if (!$user) {
            $user = $this->security->getUser();
        }

        if (!$user instanceof User) {
            return false;
        }

        // Super admin check (optional, if you have a specific role for it)
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // Check if user has the permission via their roles
        foreach ($user->getUserRoles() as $role) {
            foreach ($role->getPermissions() as $permission) {
                if ($permission->getIdentifier() === $permissionIdentifier) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
