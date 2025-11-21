<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use App\Service\TenantResolver;

class SecurityController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    #[Route(path: '/admin/login', name: 'admin_login')]
    public function adminLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $tenant = $this->tenantResolver->resolve();

        return $this->render('security/admin_login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'tenant' => $tenant
        ]);
    }

    #[Route(path: '/admin/logout', name: 'admin_logout')]
    public function adminLogout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/employee/login', name: 'employee_login')]
    public function employeeLogin(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('employee_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $tenant = $this->tenantResolver->resolve();

        return $this->render('security/employee_login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'tenant' => $tenant
        ]);
    }

    #[Route(path: '/employee/logout', name: 'employee_logout')]
    public function employeeLogout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
