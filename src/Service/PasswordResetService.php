<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class PasswordResetService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordResetTokenRepository $tokenRepository
    ) {}

    public function createResetToken(User $user): PasswordResetToken
    {
        // 既存のトークンを削除
        $this->tokenRepository->deleteUserTokens($user);

        // 新しいトークンを生成
        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setToken(bin2hex(random_bytes(32)));
        $token->setExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function validateToken(string $tokenString): ?PasswordResetToken
    {
        return $this->tokenRepository->findValidToken($tokenString);
    }

    public function markTokenAsUsed(PasswordResetToken $token): void
    {
        $token->setUsed(true);
        $this->entityManager->flush();
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->deleteExpiredTokens();
    }
}
