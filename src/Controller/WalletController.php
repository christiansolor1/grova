<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Wallet\Entity\WalletEntry;
use App\Module\Personal\Wallet\Repository\WalletCategoryRepository;
use App\Module\Personal\Wallet\Repository\WalletEntryRepository;
use App\Service\MenuTreeBuilder;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/wallet', name: 'grova_wallet_')]
final class WalletController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly TenantContext $tenantContext,
        private readonly WalletEntryRepository $entryRepo,
        private readonly WalletCategoryRepository $categoryRepo,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $now   = new \DateTimeImmutable();
        $year  = (int) $now->format('Y');
        $month = (int) $now->format('n');

        $tree = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));

        $entries        = $this->entryRepo->findLatest(40);
        $categories     = $this->categoryRepo->findAllOrdered();
        $saldo          = $this->entryRepo->getSaldoTotal();
        $ingresosMes    = $this->entryRepo->getSumByTipoAndMonth('ingreso', $year, $month);
        $gastosMes      = $this->entryRepo->getSumByTipoAndMonth('gasto',   $year, $month);

        return $this->render('workspace/pages/wallet/index.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'wallet',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'entries'                 => $entries,
            'categories'              => $categories,
            'saldo'                   => $saldo,
            'ingresos_mes'            => $ingresosMes,
            'gastos_mes'              => $gastosMes,
            'mes_label'               => $now->format('F Y'),
        ]);
    }

    #[Route('/entry/create', name: 'entry_create', methods: ['POST'])]
    public function createEntry(
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('wallet_entry', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');

            return $this->redirectToRoute('grova_wallet_index', ['_locale' => $request->getLocale()]);
        }

        $tipo      = $request->request->get('tipo', 'gasto');
        $monto     = (string) $request->request->get('monto', '0');
        $descripcion = trim((string) $request->request->get('descripcion', ''));
        $fechaStr  = (string) $request->request->get('fecha', date('Y-m-d'));
        $categoryId = $request->request->get('category_id');

        if (!in_array($tipo, ['ingreso', 'gasto'], true) || (float) $monto <= 0) {
            $this->addFlash('danger', 'Datos inválidos. El monto debe ser mayor a 0.');

            return $this->redirectToRoute('grova_wallet_index', ['_locale' => $request->getLocale()]);
        }

        $entry = new WalletEntry();
        $entry->setTipo($tipo);
        $entry->setMonto(number_format(abs((float) $monto), 2, '.', ''));
        $entry->setDescripcion($descripcion !== '' ? $descripcion : null);
        $entry->setFecha(new \DateTimeImmutable($fechaStr));

        if ($categoryId !== null && $categoryId !== '') {
            $cat = $this->categoryRepo->find((int) $categoryId);
            if ($cat !== null) {
                $entry->setCategory($cat);
            }
        }

        $em->persist($entry);
        $em->flush();

        $this->addFlash('success', sprintf(
            '%s de $%s registrado correctamente.',
            $tipo === 'ingreso' ? 'Ingreso' : 'Gasto',
            number_format(abs((float) $monto), 2)
        ));

        return $this->redirectToRoute('grova_wallet_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/entry/{id}/delete', name: 'entry_delete', methods: ['POST'])]
    public function deleteEntry(
        int $id,
        Request $request,
        #[Autowire(service: 'doctrine.orm.tenant_entity_manager')]
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('wallet_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');

            return $this->redirectToRoute('grova_wallet_index', ['_locale' => $request->getLocale()]);
        }

        $entry = $this->entryRepo->find($id);
        if ($entry !== null) {
            $em->remove($entry);
            $em->flush();
            $this->addFlash('success', 'Movimiento eliminado.');
        }

        return $this->redirectToRoute('grova_wallet_index', ['_locale' => $request->getLocale()]);
    }
}
