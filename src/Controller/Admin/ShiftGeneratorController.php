<?php

namespace App\Controller\Admin;

use App\Entity\ShiftTemplate;
use App\Form\ShiftTemplateType;
use App\Repository\ShiftTemplateRepository;
use App\Service\ShiftGeneratorService;
use App\Service\TenantResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/shift-generator')]
class ShiftGeneratorController extends AbstractController
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'admin_shift_generator_index', methods: ['GET'])]
    public function index(ShiftTemplateRepository $repository): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        return $this->render('admin/shift_generator/index.html.twig', [
            'tenant' => $tenant,
            'templates' => $repository->findBy(['tenant' => $tenant]),
        ]);
    }

    #[Route('/template/new', name: 'admin_shift_template_new', methods: ['GET', 'POST'])]
    public function newTemplate(Request $request): Response
    {
        $tenant = $this->tenantResolver->resolve();
        $template = new ShiftTemplate();
        $template->setTenant($tenant);

        $form = $this->createForm(ShiftTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->addFlash('success', 'シフトテンプレートを作成しました。');
            return $this->redirectToRoute('admin_shift_generator_index');
        }

        return $this->render('admin/shift_generator/template_form.html.twig', [
            'tenant' => $tenant,
            'form' => $form->createView(),
            'template' => null,
        ]);
    }

    #[Route('/template/{id}/edit', name: 'admin_shift_template_edit', methods: ['GET', 'POST'])]
    public function editTemplate(Request $request, ShiftTemplate $template): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($template->getTenant() !== $tenant) {
            throw $this->createNotFoundException('テンプレートが見つかりません。');
        }

        $form = $this->createForm(ShiftTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'シフトテンプレートを更新しました。');
            return $this->redirectToRoute('admin_shift_generator_index');
        }

        return $this->render('admin/shift_generator/template_form.html.twig', [
            'tenant' => $tenant,
            'form' => $form->createView(),
            'template' => $template,
        ]);
    }

    #[Route('/template/{id}/delete', name: 'admin_shift_template_delete', methods: ['POST'])]
    public function deleteTemplate(Request $request, ShiftTemplate $template): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        if ($template->getTenant() !== $tenant) {
            throw $this->createNotFoundException('テンプレートが見つかりません。');
        }

        if ($this->isCsrfTokenValid('delete'.$template->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($template);
            $this->entityManager->flush();
            $this->addFlash('success', 'テンプレートを削除しました。');
        }

        return $this->redirectToRoute('admin_shift_generator_index');
    }

    #[Route('/generate', name: 'admin_shift_generate', methods: ['POST'])]
    public function generate(Request $request, ShiftGeneratorService $generator): Response
    {
        $tenant = $this->tenantResolver->resolve();
        
        $start = new \DateTime($request->request->get('start_date'));
        $end = new \DateTime($request->request->get('end_date'));

        if ($start > $end) {
            $this->addFlash('error', '開始日は終了日より前である必要があります。');
            return $this->redirectToRoute('admin_shift_generator_index');
        }

        $count = $generator->generateShifts($tenant, $start, $end);

        $this->addFlash('success', $count . '件のシフトを自動生成しました。');
        return $this->redirectToRoute('admin_schedule_index');
    }
}
