<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use App\Repository\PermissionRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/roles')]
class RoleManagementController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_roles_index')]
    public function index(RoleRepository $roleRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');

        $queryBuilder = $roleRepository->createQueryBuilder('r');

        if ($search) {
            $queryBuilder
                ->where('r.name LIKE :search OR r.identifier LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $roles = $queryBuilder
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/roles/index.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'roles' => $roles,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'admin_roles_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($role);
            $this->entityManager->flush();

            $this->addFlash('success', 'ロールを作成しました。');
            return $this->redirectToRoute('admin_roles_index');
        }

        return $this->render('admin/roles/form.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'form' => $form,
            'isEdit' => false
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_roles_edit', methods: ['GET', 'POST'])]
    public function edit(Role $role, Request $request): Response
    {
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'ロール情報を更新しました。');
            return $this->redirectToRoute('admin_roles_index');
        }

        return $this->render('admin/roles/form.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'form' => $form,
            'isEdit' => true,
            'role' => $role
        ]);
    }

    #[Route('/{id}/permissions', name: 'admin_roles_permissions', methods: ['GET', 'POST'])]
    public function permissions(Role $role, Request $request, PermissionRepository $permissionRepository): Response
    {
        if ($request->isMethod('POST')) {
            $selectedPermissions = $request->request->all('permissions') ?? [];
            
            // 既存の権限をクリア
            foreach ($role->getPermissions() as $permission) {
                $role->removePermission($permission);
            }

            // 新しい権限を追加
            foreach ($selectedPermissions as $permissionId) {
                $permission = $permissionRepository->find($permissionId);
                if ($permission) {
                    $role->addPermission($permission);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', '権限を更新しました。');
            return $this->redirectToRoute('admin_roles_index');
        }

        $allPermissions = $permissionRepository->findAll();

        return $this->render('admin/roles/permissions.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'role' => $role,
            'allPermissions' => $allPermissions
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_roles_delete', methods: ['POST'])]
    public function delete(Role $role): Response
    {
        $this->entityManager->remove($role);
        $this->entityManager->flush();

        $this->addFlash('success', 'ロールを削除しました。');
        return $this->redirectToRoute('admin_roles_index');
    }
}
