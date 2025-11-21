<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class AccessDeniedListener
{
    public function __construct(
        private Environment $twig
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            $request = $event->getRequest();
            
            // 管理画面か従業員画面かを判定
            $isAdmin = str_starts_with($request->getPathInfo(), '/admin');
            $isEmployee = str_starts_with($request->getPathInfo(), '/employee');
            
            $template = 'error/access_denied.html.twig';
            $layout = $isAdmin ? 'admin/layout.html.twig' : ($isEmployee ? 'employee/layout.html.twig' : 'base.html.twig');
            
            try {
                $html = $this->twig->render($template, [
                    'layout' => $layout,
                    'isAdmin' => $isAdmin,
                    'isEmployee' => $isEmployee
                ]);
                
                $response = new Response($html, Response::HTTP_FORBIDDEN);
                $event->setResponse($response);
            } catch (\Exception $e) {
                // テンプレートがない場合はシンプルなレスポンス
                $response = new Response('権限がありません', Response::HTTP_FORBIDDEN);
                $event->setResponse($response);
            }
        }
    }
}
