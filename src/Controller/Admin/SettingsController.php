<?php

namespace App\Controller\Admin;

use App\Entity\Tenant;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'admin_settings_index')]
    public function index(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $user = $this->getUser();
        $activeTab = $request->query->get('tab', 'tenant');

        return $this->render('admin/settings/index.html.twig', [
            'tenant' => $tenant,
            'user' => $user,
            'activeTab' => $activeTab
        ]);
    }

    #[Route('/tenant', name: 'admin_settings_tenant', methods: ['POST'])]
    public function updateTenant(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        $tenant->setName($request->request->get('tenant_name'));
        // 将来的に追加: 住所、電話番号、営業時間など

        $this->entityManager->flush();

        $this->addFlash('success', 'テナント設定を更新しました。');
        return $this->redirectToRoute('admin_settings_index', ['tab' => 'tenant']);
    }

    #[Route('/system', name: 'admin_settings_system', methods: ['POST'])]
    public function updateSystem(Request $request): Response
    {
        // システム設定の保存（将来的に実装）
        // タイムゾーン、言語、通知設定など
        
        $this->addFlash('success', 'システム設定を更新しました。');
        return $this->redirectToRoute('admin_settings_index', ['tab' => 'system']);
    }

    #[Route('/account', name: 'admin_settings_account', methods: ['POST'])]
    public function updateAccount(Request $request): Response
    {
        $user = $this->getUser();
        
        $name = $request->request->get('name');
        if ($name) {
            $user->setName($name);
        }

        $newPassword = $request->request->get('new_password');
        if ($newPassword) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'アカウント設定を更新しました。');
        return $this->redirectToRoute('admin_settings_index', ['tab' => 'account']);
    }
}
