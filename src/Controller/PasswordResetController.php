<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/password-reset')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private PasswordResetService $resetService,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/request', name: 'password_reset_request', methods: ['GET', 'POST'])]
    public function request(Request $request, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                $token = $this->resetService->createResetToken($user);
                
                // リセットURLを生成
                $resetUrl = $this->generateUrl('password_reset_reset', [
                    'token' => $token->getToken()
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                // TODO: メール送信（現在は画面に表示）
                $this->addFlash('success', 'パスワードリセットリンクを送信しました。');
                $this->addFlash('info', 'デバッグ用URL: ' . $resetUrl);
            } else {
                // セキュリティのため、メールアドレスが存在しない場合も同じメッセージを表示
                $this->addFlash('success', 'パスワードリセットリンクを送信しました。');
            }

            return $this->redirectToRoute('password_reset_request');
        }

        return $this->render('password_reset/request.html.twig');
    }

    #[Route('/reset/{token}', name: 'password_reset_reset', methods: ['GET', 'POST'])]
    public function reset(Request $request, string $token): Response
    {
        $resetToken = $this->resetService->validateToken($token);

        if (!$resetToken || !$resetToken->isValid()) {
            $this->addFlash('error', 'このリセットリンクは無効または期限切れです。');
            return $this->redirectToRoute('password_reset_request');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'パスワードが一致しません。');
                return $this->redirectToRoute('password_reset_reset', ['token' => $token]);
            }

            if (strlen($password) < 6) {
                $this->addFlash('error', 'パスワードは6文字以上で入力してください。');
                return $this->redirectToRoute('password_reset_reset', ['token' => $token]);
            }

            // パスワードを更新
            $user = $resetToken->getUser();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // トークンを使用済みにする
            $this->resetService->markTokenAsUsed($resetToken);
            $this->entityManager->flush();

            $this->addFlash('success', 'パスワードを変更しました。新しいパスワードでログインしてください。');
            return $this->redirectToRoute('admin_login');
        }

        return $this->render('password_reset/reset.html.twig', [
            'token' => $token
        ]);
    }
}
