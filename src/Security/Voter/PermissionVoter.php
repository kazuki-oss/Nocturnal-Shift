<?php

namespace App\Security\Voter;

use App\Service\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    public function __construct(
        private PermissionService $permissionService
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        // We support all string attributes as potential permission identifiers
        // You might want to add a prefix or specific logic if this is too broad
        return is_string($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user) {
            return false;
        }

        // Use our service to check the permission
        return $this->permissionService->can($attribute, $user);
    }
}
