<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use App\Service\MenuTreeBuilder;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class MenuBuilderController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly EntityManagerInterface $entityManager,
        private readonly MenuRepository $menuRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/config-menu-builder', name: 'grova_page_config_menu_builder', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function index(Request $request): Response
    {
        return match ($request->getMethod()) {
            Request::METHOD_GET => $this->renderPage(),
            Request::METHOD_POST => $this->handleCreate($request),
            Request::METHOD_PUT => $this->handleUpdate($request),
            Request::METHOD_DELETE => $this->handleDelete($request),
            default => new JsonResponse(['success' => false, 'message' => $this->t('Método no permitido.')], Response::HTTP_METHOD_NOT_ALLOWED),
        };
    }

    #[Route('/config-menu-builder/export', name: 'grova_page_config_menu_builder_export', methods: ['GET'])]
    public function export(): Response
    {
        $menus = $this->menuRepository->findAll();
        usort($menus, static function (Menu $a, Menu $b): int {
            $pa = $a->getParentKey() ?? '';
            $pb = $b->getParentKey() ?? '';
            $cmp = $pa <=> $pb;
            if ($cmp !== 0) return $cmp;
            $cmp2 = $a->getSortOrder() <=> $b->getSortOrder();
            if ($cmp2 !== 0) return $cmp2;
            return $a->getMenuKey() <=> $b->getMenuKey();
        });

        $items = array_map(static function (Menu $m): array {
            return [
                'menuKey' => $m->getMenuKey(),
                'parentKey' => $m->getParentKey(),
                'label' => $m->getLabel(),
                'icon' => $m->getIcon(),
                'status' => $m->getStatus(),
                'sortOrder' => $m->getSortOrder(),
                'showInSidebar' => $m->isShowInSidebar(),
                'devOnly' => $m->isDevOnly(),
                'requiredRole' => $m->getRequiredRole(),
                'uiBadge' => $m->getUiBadge(),
                'uiStyle' => $m->getUiStyle(),
            ];
        }, $menus);

        $payload = [
            'version' => 1,
            'exportedAt' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!\is_string($json)) {
            return new Response($this->t('No se pudo exportar.'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $filename = 'grova-menu-backup-'.(new \DateTimeImmutable('now'))->format('Ymd-His').'.json';
        $response = new Response($json);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/config-menu-builder/import', name: 'grova_page_config_menu_builder_import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        try {
            $token = (string) ($request->request->get('_token') ?? $request->headers->get('X-CSRF-TOKEN', ''));
            if (!$this->isCsrfTokenValid('menu_governance_save', $token)) {
                throw new \InvalidArgumentException($this->t('Token CSRF inválido.'));
            }

            $replace = filter_var($request->request->get('replace', '0'), FILTER_VALIDATE_BOOL);

            $file = $request->files->get('file');
            $raw = null;
            if ($file !== null && method_exists($file, 'getContent')) {
                $raw = $file->getContent();
            }
            if (!\is_string($raw) || trim($raw) === '') {
                $raw = $request->getContent();
            }
            if (!\is_string($raw) || trim($raw) === '') {
                throw new \InvalidArgumentException($this->t('No se recibió archivo JSON.'));
            }

            $decoded = json_decode($raw, true);
            if (!\is_array($decoded)) {
                throw new \InvalidArgumentException($this->t('JSON inválido.'));
            }
            $version = (int) ($decoded['version'] ?? 0);
            if ($version !== 1) {
                throw new \InvalidArgumentException($this->t('Versión de respaldo no soportada.'));
            }
            $items = $decoded['items'] ?? [];
            if (!\is_array($items)) {
                throw new \InvalidArgumentException($this->t('Formato inválido: items.'));
            }

            return $this->successResponse($this->applyImport($items, (bool) $replace));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable) {
            return $this->errorResponse($this->t('No se pudo importar el JSON.'));
        }
    }

    private function renderPage(): Response
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return $this->render('workspace/pages/config/menu-builder.html.twig', [
            'menu_tree' => $tree,
            'active_menu_key' => 'config-menu-builder',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
        ]);
    }

    private function handleCreate(Request $request): JsonResponse
    {
        try {
            $data = $this->requestData($request);
            $this->assertCsrf($request, $data);

            $mode = (string) ($data['create_mode'] ?? 'single');
            if ($mode === 'bundle') {
                $message = $this->saveBundleMenu($data);
            } else {
                $this->saveSingleMenuItem($data);
                $message = $this->t('Ítem de menú creado correctamente.');
            }

            return $this->successResponse($message);
        } catch (UniqueConstraintViolationException) {
            return $this->errorResponse($this->t('El Path ya existe. Usa un Path diferente.'));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable) {
            return $this->errorResponse($this->t('No se pudo guardar el menú. Revisa los datos e intenta de nuevo.'));
        }
    }

    private function handleUpdate(Request $request): JsonResponse
    {
        try {
            $data = $this->requestData($request);
            $this->assertCsrf($request, $data);

            if (isset($data['bulkSort']) && \is_array($data['bulkSort'])) {
                $message = $this->applyBulkSort($data['bulkSort']);

                return $this->successResponse($message);
            }

            $menuKey = trim((string) ($data['menuKey'] ?? ''));
            if ($menuKey === '') {
                throw new \InvalidArgumentException($this->t('menuKey es obligatorio para editar.'));
            }

            $menu = $this->menuRepository->findOneBy(['menuKey' => $menuKey]);
            if ($menu === null) {
                throw new \InvalidArgumentException($this->t('No se encontró el ítem de menú a editar.'));
            }

            $label = trim((string) ($data['label'] ?? $menu->getLabel()));
            $icon = trim((string) ($data['icon'] ?? $menu->getIcon()));
            $status = trim((string) ($data['status'] ?? $menu->getStatus()));
            $sortOrder = (int) ($data['sortOrder'] ?? $menu->getSortOrder());
            $positionIndexRaw = $data['positionIndex'] ?? null;
            $uiBadge = array_key_exists('uiBadge', $data) ? (string) $data['uiBadge'] : (string) ($menu->getUiBadge() ?? '');
            $uiStyleRaw = array_key_exists('uiStyle', $data) ? $data['uiStyle'] : $menu->getUiStyle();
            $showInSidebar = filter_var($data['showInSidebar'] ?? $menu->isShowInSidebar(), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $devOnly = filter_var($data['devOnly'] ?? $menu->isDevOnly(), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $parentKeyRaw = array_key_exists('parentKey', $data) ? (string) $data['parentKey'] : (string) ($menu->getParentKey() ?? '');
            $parentKey = trim($parentKeyRaw) === '' ? null : trim($parentKeyRaw);

            if ($label === '') {
                throw new \InvalidArgumentException($this->t('El label es obligatorio.'));
            }
            if (\strlen($label) > 180) {
                throw new \InvalidArgumentException($this->t('El label excede la longitud permitida.'));
            }
            if (!\in_array($status, ['pendiente', 'hecho'], true)) {
                throw new \InvalidArgumentException($this->t('Estado inválido.'));
            }
            if ($parentKey === $menuKey) {
                throw new \InvalidArgumentException($this->t('Un menú no puede ser su propio padre.'));
            }
            if ($parentKey !== null && $this->menuRepository->findOneBy(['menuKey' => $parentKey]) === null) {
                throw new \InvalidArgumentException($this->t('El elemento padre no existe.'));
            }
            if ($showInSidebar === null || $devOnly === null) {
                throw new \InvalidArgumentException($this->t('Valores booleanos inválidos para sidebar/dev.'));
            }

            $menu
                ->setLabel($label)
                ->setIcon($this->normalizeIcon($icon))
                ->setSortOrder($sortOrder)
                ->setStatus($status)
                ->setShowInSidebar($showInSidebar)
                ->setDevOnly($devOnly)
                ->setParentKey($parentKey)
                ->setUiBadge(trim($uiBadge) === '' ? null : trim($uiBadge))
                ->setUiStyle($this->normalizeUiStyle($uiStyleRaw));

            $positionIndex = null;
            if ($positionIndexRaw !== null && $positionIndexRaw !== '') {
                if (!is_numeric($positionIndexRaw)) {
                    throw new \InvalidArgumentException($this->t('Posición inválida.'));
                }
                $positionIndex = max(0, (int) $positionIndexRaw);
            }
            if ($positionIndex !== null) {
                $this->applyReposition($menu, $positionIndex);
            }

            $this->entityManager->flush();

            return $this->successResponse($this->t('Ítem actualizado correctamente.'));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable) {
            return $this->errorResponse($this->t('No se pudo actualizar el ítem.'));
        }
    }

    /**
     * @param array<int, array<string, mixed>> $bulkSort
     */
    private function applyBulkSort(array $bulkSort): string
    {
        if ($bulkSort === []) {
            throw new \InvalidArgumentException($this->t('No hay cambios para guardar.'));
        }

        foreach ($bulkSort as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $menuKey = trim((string) ($row['menuKey'] ?? ''));
            if ($menuKey === '') {
                continue;
            }

            $menu = $this->menuRepository->findOneBy(['menuKey' => $menuKey]);
            if ($menu === null) {
                throw new \InvalidArgumentException($this->t('No se encontró el menú "%menu_key%".', ['%menu_key%' => $menuKey]));
            }

            $sortOrder = (int) ($row['sortOrder'] ?? $menu->getSortOrder());
            $parentKeyRaw = array_key_exists('parentKey', $row) ? (string) $row['parentKey'] : (string) ($menu->getParentKey() ?? '');
            $parentKey = trim($parentKeyRaw) === '' ? null : trim($parentKeyRaw);

            if ($parentKey !== null && $parentKey === $menuKey) {
                throw new \InvalidArgumentException($this->t('Un menú no puede ser su propio padre.'));
            }
            if ($parentKey !== null && $this->menuRepository->findOneBy(['menuKey' => $parentKey]) === null) {
                throw new \InvalidArgumentException($this->t('El padre "%parent_key%" no existe.', ['%parent_key%' => $parentKey]));
            }

            $menu
                ->setSortOrder(max(1, $sortOrder))
                ->setParentKey($parentKey);
        }

        $this->entityManager->flush();

        return $this->t('Orden guardado correctamente.');
    }

    private function handleDelete(Request $request): JsonResponse
    {
        try {
            $data = $this->requestData($request);
            $this->assertCsrf($request, $data);

            $menuKey = trim((string) ($data['menuKey'] ?? ''));
            if ($menuKey === '') {
                throw new \InvalidArgumentException($this->t('menuKey es obligatorio para eliminar.'));
            }

            $menu = $this->menuRepository->findOneBy(['menuKey' => $menuKey]);
            if ($menu === null) {
                throw new \InvalidArgumentException($this->t('No se encontró el ítem de menú a eliminar.'));
            }

            $children = $this->menuRepository->findBy(['parentKey' => $menuKey]);
            if ($children !== []) {
                throw new \InvalidArgumentException($this->t('No puedes eliminar este ítem porque tiene hijos.'));
            }

            $this->entityManager->remove($menu);
            $this->entityManager->flush();

            return $this->successResponse($this->t('Ítem eliminado correctamente.'));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage());
        } catch (\Throwable) {
            return $this->errorResponse($this->t('No se pudo eliminar el ítem.'));
        }
    }

    private function assertCsrf(Request $request, array $data): void
    {
        $token = (string) ($data['_token'] ?? $request->headers->get('X-CSRF-TOKEN', ''));
        if (!$this->isCsrfTokenValid('menu_governance_save', $token)) {
            throw new \InvalidArgumentException($this->t('Token CSRF inválido.'));
        }
    }

    private function t(string $key, array $params = []): string
    {
        return $this->translator->trans($key, $params, 'menu_builder');
    }

    /**
     * @return array<string, mixed>
     */
    private function requestData(Request $request): array
    {
        $data = $request->request->all();
        if ($data !== []) {
            return $data;
        }

        $json = $request->getContent();
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return \is_array($decoded) ? $decoded : [];
    }

    private function successResponse(string $message): JsonResponse
    {
        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'menu_tree' => $tree,
        ]);
    }

    private function errorResponse(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveSingleMenuItem(array $data): void
    {
        $key = trim((string) ($data['single_path'] ?? ''));
        $label = trim((string) ($data['single_label'] ?? ''));
        $icon = trim((string) ($data['single_icon'] ?? 'bi-list'));
        $type = (string) ($data['single_item_type'] ?? 'submenu');
        $parentKey = trim((string) ($data['single_parent_key'] ?? ''));
        $positionIndexRaw = $data['single_position_index'] ?? null;
        $status = trim((string) ($data['single_status'] ?? 'pendiente'));
        $showInSidebar = true;
        $devOnly = false;
        $uiBadge = trim((string) ($data['single_ui_badge'] ?? ''));
        $uiStyleRaw = $data['single_ui_style'] ?? null;

        if ($key === '' || $label === '') {
            throw new \InvalidArgumentException($this->t('Path y Label menú son obligatorios.'));
        }
        if (\strlen($key) > 120 || \strlen($label) > 180) {
            throw new \InvalidArgumentException($this->t('Path o Label menú exceden la longitud permitida.'));
        }
        if (!preg_match('/^[a-z0-9][a-z0-9\\-]*$/', $key)) {
            throw new \InvalidArgumentException($this->t('El Path solo permite minúsculas, números y guiones.'));
        }
        if (!\in_array($status, ['pendiente', 'hecho'], true)) {
            throw new \InvalidArgumentException($this->t('Estado inválido para el ítem.'));
        }
        if (!\in_array($type, ['principal', 'submenu', 'subopcion'], true)) {
            throw new \InvalidArgumentException($this->t('Tipo de elemento inválido.'));
        }

        $menu = new Menu();
        $menu->setMenuKey($key);
        $menu->setLabel($label);
        $menu->setIcon($this->normalizeIcon($icon));
        $menu->setSortOrder(0);
        $menu->setShowInSidebar($showInSidebar);
        $menu->setDevOnly($devOnly);
        $menu->setRequiredRole(null);
        $menu->setStatus($status);
        $menu->setUiBadge($uiBadge === '' ? null : $uiBadge);
        $menu->setUiStyle($this->normalizeUiStyle($uiStyleRaw));

        if ($type === 'principal') {
            $menu->setParentKey(null);
        } else {
            if ($parentKey === '') {
                throw new \InvalidArgumentException($this->t('Debes seleccionar un elemento padre.'));
            }
            $parent = $this->menuRepository->findOneBy(['menuKey' => $parentKey]);
            if ($parent === null) {
                throw new \InvalidArgumentException($this->t('El elemento padre no existe.'));
            }
            if ($type === 'submenu' && $parent->getParentKey() !== null) {
                throw new \InvalidArgumentException($this->t('Un submenú debe colgar de un menú principal.'));
            }
            if ($type === 'subopcion' && $parent->getParentKey() === null) {
                throw new \InvalidArgumentException($this->t('Una subopción debe colgar de un submenú.'));
            }
            $menu->setParentKey($parentKey);
        }
        $positionIndex = null;
        if ($positionIndexRaw !== null && $positionIndexRaw !== '') {
            if (!is_numeric($positionIndexRaw)) {
                throw new \InvalidArgumentException($this->t('Posición inválida.'));
            }
            $positionIndex = max(0, (int) $positionIndexRaw);
        }
        $this->applyInsertPosition($menu, $positionIndex);

        $this->entityManager->persist($menu);
        $this->entityManager->flush();
    }

    private function applyInsertPosition(Menu $menu, ?int $positionIndex): void
    {
        $siblings = $this->menuRepository->findBy(['parentKey' => $menu->getParentKey()]);
        usort($siblings, static function (Menu $a, Menu $b): int {
            $cmp = $a->getSortOrder() <=> $b->getSortOrder();
            if ($cmp !== 0) {
                return $cmp;
            }

            return ($a->getId() ?? 0) <=> ($b->getId() ?? 0);
        });

        $insertAt = $positionIndex ?? count($siblings);
        $insertAt = max(0, min($insertAt, count($siblings)));

        $ordered = $siblings;
        array_splice($ordered, $insertAt, 0, [$menu]);

        foreach ($ordered as $idx => $item) {
            $item->setSortOrder($idx + 1);
        }
    }

    private function applyReposition(Menu $menu, int $positionIndex): void
    {
        $parentKey = $menu->getParentKey();
        $siblings = $this->menuRepository->findBy(['parentKey' => $parentKey]);
        // Excluir el propio menú para reinsertarlo.
        $siblings = array_values(array_filter($siblings, static fn (Menu $m): bool => $m->getMenuKey() !== $menu->getMenuKey()));

        usort($siblings, static function (Menu $a, Menu $b): int {
            $cmp = $a->getSortOrder() <=> $b->getSortOrder();
            if ($cmp !== 0) {
                return $cmp;
            }

            return ($a->getId() ?? 0) <=> ($b->getId() ?? 0);
        });

        $insertAt = max(0, min($positionIndex, count($siblings)));
        $ordered = $siblings;
        array_splice($ordered, $insertAt, 0, [$menu]);

        foreach ($ordered as $idx => $item) {
            $item->setSortOrder($idx + 1);
        }
    }

    /**
     * @param array<int, mixed> $items
     */
    private function applyImport(array $items, bool $replace): string
    {
        $this->entityManager->wrapInTransaction(function () use ($items, $replace): void {
            if ($replace) {
                $this->entityManager->createQuery('DELETE FROM App\Entity\Menu m')->execute();
            }

            $existing = [];
            foreach ($this->menuRepository->findAll() as $m) {
                $existing[$m->getMenuKey()] = $m;
            }

            // 1) Crear entidades faltantes (por menuKey).
            foreach ($items as $row) {
                if (!\is_array($row)) continue;
                $menuKey = trim((string) ($row['menuKey'] ?? ''));
                if ($menuKey === '') continue;
                if (!isset($existing[$menuKey])) {
                    $m = new Menu();
                    $m->setMenuKey($menuKey);
                    $this->entityManager->persist($m);
                    $existing[$menuKey] = $m;
                }
            }
            $this->entityManager->flush();

            // 2) Aplicar campos.
            foreach ($items as $row) {
                if (!\is_array($row)) continue;
                $menuKey = trim((string) ($row['menuKey'] ?? ''));
                if ($menuKey === '' || !isset($existing[$menuKey])) continue;
                $m = $existing[$menuKey];

                $label = trim((string) ($row['label'] ?? $m->getLabel() ?? ''));
                if ($label === '') {
                    throw new \InvalidArgumentException($this->t('Label vacío para "%menu_key%".', ['%menu_key%' => $menuKey]));
                }
                $status = trim((string) ($row['status'] ?? $m->getStatus()));
                if (!\in_array($status, ['pendiente', 'hecho'], true)) {
                    throw new \InvalidArgumentException($this->t('Estado inválido para "%menu_key%".', ['%menu_key%' => $menuKey]));
                }

                $parentKeyRaw = array_key_exists('parentKey', $row) ? (string) $row['parentKey'] : (string) ($m->getParentKey() ?? '');
                $parentKey = trim($parentKeyRaw) === '' ? null : trim($parentKeyRaw);
                if ($parentKey !== null && $parentKey === $menuKey) {
                    throw new \InvalidArgumentException($this->t('"%menu_key%" no puede ser su propio padre.', ['%menu_key%' => $menuKey]));
                }
                if ($parentKey !== null && !isset($existing[$parentKey])) {
                    throw new \InvalidArgumentException($this->t('Padre "%parent_key%" no existe (referenciado por "%menu_key%").', ['%parent_key%' => $parentKey, '%menu_key%' => $menuKey]));
                }

                $m
                    ->setLabel($label)
                    ->setIcon($this->normalizeIcon((string) ($row['icon'] ?? $m->getIcon())))
                    ->setStatus($status)
                    ->setParentKey($parentKey)
                    ->setShowInSidebar((bool) ($row['showInSidebar'] ?? $m->isShowInSidebar()))
                    ->setDevOnly((bool) ($row['devOnly'] ?? $m->isDevOnly()))
                    ->setRequiredRole(($row['requiredRole'] ?? null) ? (string) $row['requiredRole'] : null)
                    ->setSortOrder((int) ($row['sortOrder'] ?? $m->getSortOrder()))
                    ->setUiBadge(trim((string) ($row['uiBadge'] ?? $m->getUiBadge() ?? '')) === '' ? null : trim((string) ($row['uiBadge'] ?? $m->getUiBadge())))
                    ->setUiStyle($this->normalizeUiStyle($row['uiStyle'] ?? $m->getUiStyle()));
            }

            // 3) Normalizar sortOrder por nivel/padre.
            $byParent = [];
            foreach ($existing as $m) {
                $pk = $m->getParentKey() ?? '__ROOT__';
                $byParent[$pk] ??= [];
                $byParent[$pk][] = $m;
            }
            foreach ($byParent as $list) {
                usort($list, static function (Menu $a, Menu $b): int {
                    $cmp = $a->getSortOrder() <=> $b->getSortOrder();
                    if ($cmp !== 0) return $cmp;
                    return $a->getMenuKey() <=> $b->getMenuKey();
                });
                foreach ($list as $idx => $m) {
                    $m->setSortOrder($idx + 1);
                }
            }

            $this->entityManager->flush();
        });

        return $replace
            ? 'JSON importado (reemplazando todo).'
            : 'JSON importado (merge/upsert).';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveBundleMenu(array $data): string
    {
        $bundleKind = trim((string) ($data['bundle_kind'] ?? 'full'));
        $principalKey = trim((string) ($data['bundle_principal_path'] ?? ''));
        $principalLabel = trim((string) ($data['bundle_principal_label'] ?? ''));
        $submenuKey = trim((string) ($data['bundle_submenu_path'] ?? ''));
        $submenuLabel = trim((string) ($data['bundle_submenu_label'] ?? ''));
        $suboptionKey = trim((string) ($data['bundle_suboption_path'] ?? ''));
        $suboptionLabel = trim((string) ($data['bundle_suboption_label'] ?? ''));
        $parentPrincipal = trim((string) ($data['bundle_parent_principal'] ?? ''));
        $parentPrincipalSuboption = trim((string) ($data['bundle_parent_principal_suboption'] ?? ''));
        $parentSubmenu = trim((string) ($data['bundle_parent_submenu'] ?? ''));
        $status = trim((string) ($data['bundle_status'] ?? 'pendiente'));
        $baseSort = (int) ($data['bundle_base_sort_order'] ?? 80);

        if (!\in_array($bundleKind, ['full', 'submenu', 'subopcion'], true)) {
            throw new \InvalidArgumentException($this->t('Tipo de estructura inválido.'));
        }
        if (!\in_array($status, ['pendiente', 'hecho'], true)) {
            throw new \InvalidArgumentException($this->t('Estado inválido para la estructura.'));
        }

        $required = [];
        if ($bundleKind === 'full') {
            $required = [
                'Path menú principal' => $principalKey,
                'Label menú principal' => $principalLabel,
                'Path submenú' => $submenuKey,
                'Label submenú' => $submenuLabel,
                'Path subopción' => $suboptionKey,
                'Label subopción' => $suboptionLabel,
            ];
        } elseif ($bundleKind === 'submenu') {
            $required = [
                'Menú principal padre' => $parentPrincipal,
                'Path submenú' => $submenuKey,
                'Label submenú' => $submenuLabel,
                'Path subopción' => $suboptionKey,
                'Label subopción' => $suboptionLabel,
            ];
        } else {
            $required = [
                'Menú principal padre' => $parentPrincipalSuboption,
                'Submenú padre' => $parentSubmenu,
                'Path subopción' => $suboptionKey,
                'Label subopción' => $suboptionLabel,
            ];
        }
        foreach ($required as $field => $value) {
            if ($value === '') {
                throw new \InvalidArgumentException($this->t('%field% es obligatorio.', ['%field%' => $field]));
            }
        }

        $keys = $bundleKind === 'full' ? [$principalKey, $submenuKey, $suboptionKey] : ($bundleKind === 'submenu' ? [$submenuKey, $suboptionKey] : [$suboptionKey]);
        foreach ($keys as $k) {
            if (!preg_match('/^[a-z0-9][a-z0-9\\-]*$/', $k)) {
                throw new \InvalidArgumentException($this->t('Los Path solo permiten minúsculas, números y guiones.'));
            }
        }

        if ($bundleKind === 'full') {
            $this->entityManager->persist((new Menu())
                ->setMenuKey($principalKey)
                ->setParentKey(null)
                ->setLabel($principalLabel)
                ->setIcon('bi-folder')
                ->setSortOrder($baseSort)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status));
            $this->entityManager->persist((new Menu())
                ->setMenuKey($submenuKey)
                ->setParentKey($principalKey)
                ->setLabel($submenuLabel)
                ->setIcon('bi-list')
                ->setSortOrder($baseSort + 1)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status));
            $this->entityManager->persist((new Menu())
                ->setMenuKey($suboptionKey)
                ->setParentKey($submenuKey)
                ->setLabel($suboptionLabel)
                ->setIcon('bi-dot')
                ->setSortOrder($baseSort + 2)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status));
            $this->entityManager->flush();

            return $this->t('Estructura creada: menú principal + submenú + subopción.');
        }

        if ($bundleKind === 'submenu') {
            if ($this->menuRepository->findOneBy(['menuKey' => $parentPrincipal]) === null) {
                throw new \InvalidArgumentException($this->t('El menú principal padre no existe.'));
            }
            $this->entityManager->persist((new Menu())
                ->setMenuKey($submenuKey)
                ->setParentKey($parentPrincipal)
                ->setLabel($submenuLabel)
                ->setIcon('bi-list')
                ->setSortOrder($baseSort)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status));
            $this->entityManager->persist((new Menu())
                ->setMenuKey($suboptionKey)
                ->setParentKey($submenuKey)
                ->setLabel($suboptionLabel)
                ->setIcon('bi-dot')
                ->setSortOrder($baseSort + 1)
                ->setShowInSidebar(true)
                ->setDevOnly(false)
                ->setRequiredRole(null)
                ->setStatus($status));
            $this->entityManager->flush();

            return $this->t('Estructura creada: submenú + subopción.');
        }

        $submenuParent = $this->menuRepository->findOneBy(['menuKey' => $parentSubmenu]);
        if ($submenuParent === null) {
            throw new \InvalidArgumentException($this->t('El submenú padre no existe.'));
        }
        if ($submenuParent->getParentKey() !== $parentPrincipalSuboption) {
            throw new \InvalidArgumentException($this->t('El submenú seleccionado no pertenece al menú principal indicado.'));
        }

        $this->entityManager->persist((new Menu())
            ->setMenuKey($suboptionKey)
            ->setParentKey($parentSubmenu)
            ->setLabel($suboptionLabel)
            ->setIcon('bi-dot')
            ->setSortOrder($baseSort)
            ->setShowInSidebar(true)
            ->setDevOnly(false)
            ->setRequiredRole(null)
            ->setStatus($status));
        $this->entityManager->flush();

        return $this->t('Estructura creada: subopción bajo submenú existente.');
    }

    private function normalizeIcon(string $icon): string
    {
        $raw = strtolower(trim($icon));
        if ($raw === '') {
            return 'bi-list';
        }

        $safe = preg_replace('/[^a-z0-9\-\s]/', '', $raw) ?? '';
        $safe = trim($safe);
        if ($safe === '') {
            return 'bi-list';
        }

        preg_match('/\bbi-[a-z0-9-]+\b/', $safe, $biMatch);
        if (isset($biMatch[0])) {
            return $biMatch[0];
        }

        preg_match('/\bfa-[a-z0-9-]+\b/', $safe, $faMatch);
        if (isset($faMatch[0])) {
            preg_match('/\bfa-(solid|regular|brands|light|duotone)\b/', $safe, $faStyleMatch);
            $style = isset($faStyleMatch[0]) ? $faStyleMatch[0] : 'fa-solid';

            return $style.' '.$faMatch[0];
        }

        if (preg_match('/^[a-z0-9-]+$/', $safe)) {
            return 'bi-'.$safe;
        }

        throw new \InvalidArgumentException($this->t('Ícono inválido. Usa Bootstrap Icons (bi-*) o Font Awesome (fa-*).'));
    }

    /**
     * Acepta null/string JSON/array con llaves permitidas.
     * Retorna JSON string normalizado o null.
     *
     * @param mixed $raw
     */
    private function normalizeUiStyle(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = $raw;
        if (\is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (!\is_array($decoded)) {
                return null;
            }
        }
        if (!\is_array($decoded)) {
            return null;
        }
        $allowed = ['variant', 'bg', 'text', 'border', 'hoverBg', 'hoverText'];
        $out = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $decoded)) {
                continue;
            }
            $v = $decoded[$k];
            if ($k === 'variant') {
                $vv = strtolower(trim((string) $v));
                if ($vv === '' || !\in_array($vv, ['premium', 'custom'], true)) {
                    continue;
                }
                $out[$k] = $vv;
                continue;
            }
            $vv = strtoupper(trim((string) $v));
            if (!preg_match('/^#[0-9A-F]{6}$/', $vv)) {
                continue;
            }
            $out[$k] = $vv;
        }
        if ($out === []) {
            return null;
        }
        $json = json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return \is_string($json) ? $json : null;
    }
}
