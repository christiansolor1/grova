<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Repository\ModuloTenantRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

/**
 * Enriquece el payload JWT con tenant, módulos y roles del usuario.
 *
 * Tag en services.yaml:
 *   { name: kernel.event_listener, event: lexik_jwt_authentication.on_jwt_created, method: onJWTCreated }
 */
final class JWTCreatedListener
{
    public function __construct(
        private readonly ModuloTenantRepository $moduloTenantRepository,
    ) {
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $tenant = $user->getTenant();
        $payload = $event->getData();

        if ($tenant !== null) {
            $payload['tenant'] = $tenant->getSlug();
            $payload['modulos'] = $this->moduloTenantRepository->findActiveKeysForTenant($tenant);
        }

        $payload['roles'] = $user->getRoles();

        $event->setData($payload);
    }
}
