<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Module\Personal\Agenda\Entity\AgendaEvento;
use App\Module\Personal\Agenda\Repository\AgendaEventoRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/agenda', name: 'api_agenda_')]
class AgendaApiController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AgendaEventoRepository $repo,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function listar(): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $eventos = $this->repo->findByTenant($tenantId);

        return $this->json(array_map(fn($e) => $this->serializar($e), $eventos));
    }

    #[Route('/proximos', name: 'proximos', methods: ['GET'])]
    public function proximos(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $dias    = (int) ($request->query->get('dias') ?? 60);
        $eventos = $this->repo->findProximos($tenantId, $dias);

        usort($eventos, fn($a, $b) => $a->diasRestantes() <=> $b->diasRestantes());

        return $this->json(array_map(fn($e) => $this->serializar($e), $eventos));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function crear(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return $this->json(['error' => 'El nombre es requerido'], 422);
        }

        $tipo = $data['tipo'] ?? 'evento';
        if (!in_array($tipo, ['cumpleanios', 'evento', 'recordatorio'], true)) {
            $tipo = 'evento';
        }

        $fechaStr = $data['fecha'] ?? date('Y-m-d');
        try {
            $fecha = new \DateTimeImmutable($fechaStr);
        } catch (\Exception) {
            $fecha = new \DateTimeImmutable();
        }

        $avisos = array_map('intval', (array) ($data['avisos'] ?? [1]));

        $evento = new AgendaEvento();
        $evento->setTenantId($tenantId);
        $evento->setNombre($nombre);
        $evento->setApodo($data['apodo'] ?? null);
        $evento->setFecha($fecha);
        $evento->setTipo($tipo);
        $evento->setParentesco($tipo === 'cumpleanios' ? ($data['parentesco'] ?? null) : null);
        $evento->setRecurrente((bool) ($data['recurrente'] ?? false));
        $evento->setHora($tipo !== 'cumpleanios' ? ($data['hora'] ?? null) : null);
        $evento->setLugar($tipo !== 'cumpleanios' ? ($data['lugar'] ?? null) : null);
        $evento->setUrl($tipo !== 'cumpleanios' ? ($data['url'] ?? null) : null);
        $evento->setNotas($data['notas'] ?? null);
        $evento->setColor($data['color'] ?? null);
        $evento->setNotificarCorreo((bool) ($data['notificarCorreo'] ?? true));
        $evento->setNotificarPush((bool) ($data['notificarPush'] ?? false));
        $evento->setAvisos($avisos);

        $em->persist($evento);
        $em->flush();

        return $this->json($this->serializar($evento), 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function editar(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $evento = $this->repo->find($id);
        if ($evento === null || $evento->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $nombre = trim((string) ($data['nombre'] ?? $evento->getNombre()));
        if ($nombre === '') {
            return $this->json(['error' => 'El nombre es requerido'], 422);
        }

        $tipo = $data['tipo'] ?? $evento->getTipo();
        if (!in_array($tipo, ['cumpleanios', 'evento', 'recordatorio'], true)) {
            $tipo = 'evento';
        }

        $fechaStr = $data['fecha'] ?? $evento->getFecha()->format('Y-m-d');
        try {
            $fecha = new \DateTimeImmutable($fechaStr);
        } catch (\Exception) {
            $fecha = $evento->getFecha();
        }

        $avisos = array_key_exists('avisos', $data)
            ? array_map('intval', (array) $data['avisos'])
            : $evento->getAvisos();

        $evento->setNombre($nombre);
        $evento->setApodo($data['apodo'] ?? $evento->getApodo());
        $evento->setFecha($fecha);
        $evento->setTipo($tipo);
        $evento->setParentesco($tipo === 'cumpleanios' ? ($data['parentesco'] ?? $evento->getParentesco()) : null);
        $evento->setRecurrente(array_key_exists('recurrente', $data) ? (bool) $data['recurrente'] : $evento->isRecurrente());
        $evento->setHora($tipo !== 'cumpleanios' ? ($data['hora'] ?? $evento->getHora()) : null);
        $evento->setLugar($tipo !== 'cumpleanios' ? ($data['lugar'] ?? $evento->getLugar()) : null);
        $evento->setUrl($tipo !== 'cumpleanios' ? ($data['url'] ?? $evento->getUrl()) : null);
        $evento->setNotas(array_key_exists('notas', $data) ? $data['notas'] : $evento->getNotas());
        $evento->setColor(array_key_exists('color', $data) ? $data['color'] : $evento->getColor());
        $evento->setNotificarCorreo(array_key_exists('notificarCorreo', $data) ? (bool) $data['notificarCorreo'] : $evento->isNotificarCorreo());
        $evento->setNotificarPush(array_key_exists('notificarPush', $data) ? (bool) $data['notificarPush'] : $evento->isNotificarPush());
        $evento->setAvisos($avisos);

        $em->flush();

        return $this->json($this->serializar($evento));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function eliminar(int $id, EntityManagerInterface $em): JsonResponse
    {
        $tenantId = $this->tenantContext->getTenant()?->getId();
        if ($tenantId === null) {
            return $this->json(['error' => 'Tenant no encontrado'], 403);
        }

        $evento = $this->repo->find($id);
        if ($evento === null || $evento->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'No encontrado'], 404);
        }

        $em->remove($evento);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    private function serializar(AgendaEvento $e): array
    {
        return [
            'id'              => $e->getId(),
            'nombre'          => $e->getNombre(),
            'apodo'           => $e->getApodo(),
            'nombreVisible'   => $e->getNombreVisible(),
            'parentesco'      => $e->getParentesco(),
            'fecha'           => $e->getFecha()->format('Y-m-d'),
            'tipo'            => $e->getTipo(),
            'recurrente'      => $e->isRecurrente(),
            'hora'            => $e->getHora(),
            'lugar'           => $e->getLugar(),
            'url'             => $e->getUrl(),
            'notas'           => $e->getNotas(),
            'color'           => $e->getColor(),
            'notificarCorreo' => $e->isNotificarCorreo(),
            'notificarPush'   => $e->isNotificarPush(),
            'avisos'          => $e->getAvisos(),
            'diasRestantes'   => $e->diasRestantes(),
        ];
    }
}
