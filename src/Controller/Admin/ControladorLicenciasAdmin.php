<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Licencia;
use App\Entity\Tenant;
use App\Repository\LicenciaRepository;
use App\Repository\TenantRepository;
use App\Service\GeneradorLicencias;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/empresas/{id}/licencias', name: 'grova_admin_licencias_', requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class ControladorLicenciasAdmin extends AbstractController
{
    public function __construct(
        private readonly TenantRepository $repositorioInquilinos,
        private readonly LicenciaRepository $repositorioLicencias,
        private readonly GeneradorLicencias $generadorLicencias,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function indice(int $id): Response
    {
        $inquilino = $this->obtenerInquilino($id);

        return $this->render('admin/licencias/indexLicencias.html.twig', [
            'inquilino' => $inquilino,
            'licencias' => $this->repositorioLicencias->findByTenant($inquilino),
        ]);
    }

    #[Route('/generar', name: 'generar', methods: ['POST'])]
    public function generar(int $id, Request $peticion): Response
    {
        $inquilino = $this->obtenerInquilino($id);

        if (!$this->isCsrfTokenValid('generar_licencia_'.$id, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_licencias_index', ['id' => $id]);
        }

        $duracion = max(1, (int) $peticion->request->get('duracion_dias', 365));
        /** @var list<string> $modulos */
        $modulos = array_values(array_filter((array) $peticion->request->all('modulos')));
        $notas = $peticion->request->get('notas') !== '' ? $peticion->request->get('notas') : null;

        try {
            $this->generadorLicencias->generar($inquilino, $duracion, $modulos, $notas);
            $this->addFlash('success', 'Licencia generada correctamente.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Error al generar la licencia: '.$e->getMessage());
        }

        return $this->redirectToRoute('grova_admin_licencias_index', ['id' => $id]);
    }

    #[Route('/{licenciaId}/revocar', name: 'revocar', requirements: ['licenciaId' => '\d+'], methods: ['POST'])]
    public function revocar(int $id, int $licenciaId, Request $peticion): Response
    {
        if (!$this->isCsrfTokenValid('revocar_licencia_'.$licenciaId, (string) $peticion->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');

            return $this->redirectToRoute('grova_admin_licencias_index', ['id' => $id]);
        }

        $licencia = $this->repositorioLicencias->find($licenciaId);

        if (!$licencia instanceof Licencia || $licencia->getTenant()->getId() !== $id) {
            $this->addFlash('error', 'Licencia no encontrada.');

            return $this->redirectToRoute('grova_admin_licencias_index', ['id' => $id]);
        }

        $this->generadorLicencias->revocar($licencia);
        $this->addFlash('success', 'Licencia revocada.');

        return $this->redirectToRoute('grova_admin_licencias_index', ['id' => $id]);
    }

    private function obtenerInquilino(int $id): Tenant
    {
        $inquilino = $this->repositorioInquilinos->find($id);

        if (!$inquilino instanceof Tenant) {
            throw $this->createNotFoundException('Empresa no encontrada.');
        }

        return $inquilino;
    }
}
