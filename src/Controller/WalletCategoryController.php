<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletCategory;
use App\Module\Personal\Wallet\Repository\WalletCategoryRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/wallet/categorias', name: 'grova_wallet_category_')]
final class WalletCategoryController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly WalletCategoryRepository $categoryRepo,
    ) {
    }

    #[Route('/data', name: 'data', methods: ['GET'])]
    public function data(): JsonResponse
    {
        $tenantId   = $this->tenantContext->getTenant()->getId();
        $categories = $this->categoryRepo->findAllOrdered($tenantId);

        $rows = array_map(fn(WalletCategory $c) => [
            'id'           => $c->getId(),
            'nombre'       => $c->getNombre(),
            'nombreCompleto' => $c->getNombreCompleto(),
            'tipo'         => $c->getTipo(),
            'icono'        => $c->getIcono(),
            'color'        => $c->getColor(),
            'parentId'     => $c->getParent()?->getId(),
            'parentNombre' => $c->getParent()?->getNombre(),
            'tokenEdit'    => $this->generateToken('wallet_category_edit_' . $c->getId()),
            'tokenDelete'  => $this->generateToken('wallet_category_delete_' . $c->getId()),
        ], $categories);

        return new JsonResponse(['categories' => $rows]);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_category_create', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $nombre = trim((string) $request->request->get('nombre', ''));
        $tipo   = (string) $request->request->get('tipo', 'gasto');

        if ($nombre === '' || !in_array($tipo, ['ingreso', 'gasto'], true)) {
            return new JsonResponse(['success' => false, 'message' => 'Nombre y tipo son obligatorios.'], 422);
        }

        $tenantId = $this->tenantContext->getTenant()->getId();
        $color    = (string) $request->request->get('color', '#64748b');

        $dup = $this->categoryRepo->findDuplicate($tenantId, $nombre, $color);
        if ($dup !== null) {
            $motivo = strtolower($dup->getNombre()) === strtolower($nombre)
                ? "Ya existe una categoría con ese nombre (\"{$dup->getNombre()}\")."
                : "El color ya está en uso por la categoría \"{$dup->getNombre()}\".";
            return new JsonResponse(['success' => false, 'message' => $motivo], 422);
        }

        $cat = new WalletCategory();
        $cat->setTenantId($tenantId);
        $cat->setNombre($nombre);
        $cat->setTipo($tipo);
        $cat->setIcono(trim((string) $request->request->get('icono', 'bi-tag')) ?: 'bi-tag');
        $cat->setColor($color);
        $this->asignarParent($cat, $request, $tenantId);

        $em->persist($cat);
        $em->flush();

        return new JsonResponse([
            'success'      => true,
            'message'      => 'Categoría creada.',
            'id'           => $cat->getId(),
            'nombre'       => $cat->getNombre(),
            'nombreCompleto' => $cat->getNombreCompleto(),
            'tipo'         => $cat->getTipo(),
            'icono'        => $cat->getIcono(),
            'color'        => $cat->getColor(),
            'parentId'     => $cat->getParent()?->getId(),
            'parentNombre' => $cat->getParent()?->getNombre(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_category_edit_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $cat = $this->categoryRepo->find($id);
        if ($cat === null) {
            return new JsonResponse(['success' => false, 'message' => 'No encontrada.'], 404);
        }

        $nombre = trim((string) $request->request->get('nombre', ''));
        if ($nombre === '') {
            return new JsonResponse(['success' => false, 'message' => 'El nombre es obligatorio.'], 422);
        }

        $color    = (string) $request->request->get('color', '#64748b');
        $tenantId = $this->tenantContext->getTenant()->getId();

        $dup = $this->categoryRepo->findDuplicate($tenantId, $nombre, $color, $id);
        if ($dup !== null) {
            $motivo = strtolower($dup->getNombre()) === strtolower($nombre)
                ? "Ya existe una categoría con ese nombre (\"{$dup->getNombre()}\")."
                : "El color ya está en uso por la categoría \"{$dup->getNombre()}\".";
            return new JsonResponse(['success' => false, 'message' => $motivo], 422);
        }

        $cat->setNombre($nombre);
        $cat->setTipo((string) $request->request->get('tipo', 'gasto'));
        $cat->setIcono(trim((string) $request->request->get('icono', 'bi-tag')) ?: 'bi-tag');
        $cat->setColor($color);
        $this->asignarParent($cat, $request, $tenantId);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Categoría actualizada.']);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('wallet_category_delete_' . $id, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        $cat = $this->categoryRepo->find($id);
        if ($cat !== null) {
            $em->remove($cat);
            $em->flush();
        }

        return new JsonResponse(['success' => true, 'message' => 'Categoría eliminada.']);
    }

    private function asignarParent(WalletCategory $cat, Request $request, int $tenantId): void
    {
        $parentId = $request->request->get('parent_id', '');
        if ($parentId !== '' && $parentId !== null) {
            $parent = $this->categoryRepo->find((int) $parentId);
            // Solo asignar si es del mismo tenant, es raíz (no subcategoría) y no es la misma
            if ($parent !== null && $parent->getTenantId() === $tenantId
                && $parent->getParent() === null && $parent->getId() !== $cat->getId()) {
                $cat->setParent($parent);
                // La subcategoría hereda el tipo del padre
                $cat->setTipo($parent->getTipo());
                return;
            }
        }
        $cat->setParent(null);
    }

    private function generateToken(string $id): string
    {
        return $this->container->get('security.csrf.token_manager')->getToken($id)->getValue();
    }
}
