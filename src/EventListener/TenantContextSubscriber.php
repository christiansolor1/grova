<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Repository\ModuloTenantRepository;
use App\Service\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * En cada request web (sesión), carga el TenantContext desde el User autenticado.
 * Para API JWT, el contexto se carga igual: lexik popula el token antes de que llegue aquí.
 */
final class TenantContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly TenantContext $tenantContext,
        private readonly ModuloTenantRepository $moduloTenantRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Prioridad -20 → corre después del firewall de seguridad (prioridad 8)
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -20],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        $tenant = $user->getTenant();

        if ($tenant === null) {
            return;
        }

        $modulos = $this->moduloTenantRepository->findActiveKeysForTenant($tenant);

        $this->tenantContext->setTenant($tenant, $modulos);
    }
}
