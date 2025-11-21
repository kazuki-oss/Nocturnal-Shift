<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\EmployeeProfile;
use App\Form\EmployeeType;
use App\Repository\UserRepository;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/employees')]
class EmployeeManagementController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'admin_employees_index')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $search = $request->query->get('search', '');

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->where('u.tenant = :tenant')
            ->setParameter('tenant', $tenant);

        if ($search) {
            $queryBuilder
                ->andWhere('u.name LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $employees = $queryBuilder
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/employees/index.html.twig', [
            'tenant' => $tenant,
            'employees' => $employees,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'admin_employees_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(EmployeeType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tenant = $this->tenantResolver->resolve();
            $user->setTenant($tenant);

            // パスワードハッシュ化
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            // EmployeeProfile作成
            if (!$user->getEmployeeProfile()) {
                $profile = new EmployeeProfile();
                $profile->setUser($user);
                $user->setEmployeeProfile($profile);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', '従業員を作成しました。');
            return $this->redirectToRoute('admin_employees_index');
        }

        return $this->render('admin/employees/form.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'form' => $form,
            'isEdit' => false
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_employees_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request): Response
    {
        $form = $this->createForm(EmployeeType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // パスワード変更があれば更新
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', '従業員情報を更新しました。');
            return $this->redirectToRoute('admin_employees_index');
        }

        return $this->render('admin/employees/form.html.twig', [
            'tenant' => $this->tenantResolver->resolve(),
            'form' => $form,
            'isEdit' => true,
            'employee' => $user
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_employees_delete', methods: ['POST'])]
    public function delete(User $user): Response
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', '従業員を削除しました。');
        return $this->redirectToRoute('admin_employees_index');
    }
}
