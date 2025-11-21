<?php

namespace App\Controller\Admin;

use App\Entity\Permission;
use App\Form\PermissionType;
use App\Repository\PermissionRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/permissions')]
class PermissionManagementController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_permissions_index')]
    public function index(PermissionRepository $permissionRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');

        $queryBuilder = $permissionRepository->createQueryBuilder('p');

        if ($search) {
            $queryBuilder
                ->where('p.name LIKE :search OR p.identifier LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $permissions = $queryBuilder
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/permissions/index.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'permissions' => $permissions,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'admin_permissions_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $permission = new Permission();
        $form = $this->createForm(PermissionType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($permission);
            $this->entityManager->flush();

            $this->addFlash('success', '権限を作成しました。');
            return $this->redirectToRoute('admin_permissions_index');
        }

        return $this->render('admin/permissions/form.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'form' => $form,
            'isEdit' => false
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_permissions_edit', methods: ['GET', 'POST'])]
    public function edit(Permission $permission, Request $request): Response
    {
        $form = $this->createForm(PermissionType::class, $permission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', '権限情報を更新しました。');
            return $this->redirectToRoute('admin_permissions_index');
        }

        return $this->render('admin/permissions/form.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'form' => $form,
            'isEdit' => true,
            'permission' => $permission
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_permissions_delete', methods: ['POST'])]
    public function delete(Permission $permission): Response
    {
        $this->entityManager->remove($permission);
        $this->entityManager->flush();

        $this->addFlash('success', '権限を削除しました。');
        return $this->redirectToRoute('admin_permissions_index');
    }
}
