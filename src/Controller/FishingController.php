<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Fishing\Entity\FishingExpense;
use App\Module\Personal\Fishing\Entity\FishingFinca;
use App\Module\Personal\Fishing\Entity\FishingLure;
use App\Module\Personal\Fishing\Entity\FishingLureResult;
use App\Module\Personal\Fishing\Entity\FishingSpot;
use App\Module\Personal\Fishing\Entity\FishingTrip;
use App\Module\Personal\Fishing\Entity\FishingTripLure;
use App\Module\Personal\Fishing\Entity\FishingTripMember;
use App\Module\Personal\Fishing\Repository\FishingFincaRepository;
use App\Module\Personal\Fishing\Repository\FishingLureRepository;
use App\Module\Personal\Fishing\Repository\FishingLureResultRepository;
use App\Module\Personal\Fishing\Repository\FishingTripRepository;
use App\Module\Personal\Fishing\Service\TideService;
use App\Service\MenuTreeBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/fishing', name: 'grova_fishing_')]
final class FishingController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly FishingFincaRepository $fincaRepo,
        private readonly FishingLureRepository $lureRepo,
        private readonly FishingLureResultRepository $lureResultRepo,
        private readonly FishingTripRepository $tripRepo,
        private readonly TideService $tideService,
        private readonly SluggerInterface $slugger,
    ) {
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $tree   = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $fincas = $this->fincaRepo->findAllOrdered();
        $trips  = $this->tripRepo->findLatest(10);
        $lures  = $this->lureRepo->findAllOrdered();

        return $this->render('workspace/pages/fishing/indexPesca.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'pesca',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'fincas'                  => $fincas,
            'trips'                   => $trips,
            'lures'                   => $lures,
        ]);
    }

    // ── Fincas ───────────────────────────────────────────────────────────────

    #[Route('/finca/create', name: 'finca_create', methods: ['POST'])]
    public function createFinca(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('fishing_finca', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
        }

        $finca = new FishingFinca();
        $finca->setNombre(trim((string) $request->request->get('nombre', '')));
        $finca->setLatitud($request->request->get('latitud') ?: null);
        $finca->setLongitud($request->request->get('longitud') ?: null);
        $finca->setDescripcion(trim((string) $request->request->get('descripcion', '')) ?: null);

        $em->persist($finca);
        $em->flush();

        $this->addFlash('success', sprintf('Finca "%s" creada.', $finca->getNombre()));
        return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/finca/{id}', name: 'finca_show', methods: ['GET'])]
    public function showFinca(int $id): Response
    {
        $finca   = $this->fincaRepo->find($id);
        if ($finca === null) {
            throw $this->createNotFoundException();
        }

        $tree    = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $results = $this->lureResultRepo->findByFinca($finca);
        $lures   = $this->lureRepo->findAllOrdered();

        return $this->render('workspace/pages/fishing/finca.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'pesca',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'finca'                   => $finca,
            'results'                 => $results,
            'lures'                   => $lures,
        ]);
    }

    // ── Mareas (consulta manual para no gastar el plan gratuito) ─────────────

    #[Route('/finca/{id}/tides', name: 'finca_tides', methods: ['GET'])]
    public function getTides(int $id, Request $request): JsonResponse
    {
        $finca = $this->fincaRepo->find($id);
        if ($finca === null || !$finca->hasCoords()) {
            return $this->json(['error' => 'Finca sin coordenadas GPS.'], 400);
        }

        $dateStr = $request->query->get('date', date('Y-m-d'));
        $date    = new \DateTimeImmutable($dateStr);

        $tides = $this->tideService->getTides(
            (float) $finca->getLatitud(),
            (float) $finca->getLongitud(),
            $date
        );

        return $this->json(['tides' => $tides, 'date' => $dateStr, 'cached' => true]);
    }

    // ── Señuelos ─────────────────────────────────────────────────────────────

    #[Route('/lure/create', name: 'lure_create', methods: ['POST'])]
    public function createLure(
        Request $request,
        EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/uploads/fishing/lures')]
        string $uploadDir,
    ): Response {
        if (!$this->isCsrfTokenValid('fishing_lure', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
        }

        $lure = new FishingLure();
        $lure->setNombre(trim((string) $request->request->get('nombre', '')));
        $lure->setMarca(trim((string) $request->request->get('marca', '')) ?: null);
        $lure->setColor(trim((string) $request->request->get('color', '')) ?: null);
        $lure->setTipo(trim((string) $request->request->get('tipo', '')) ?: null);
        $lure->setPrecio($request->request->get('precio') ? number_format(abs((float) $request->request->get('precio')), 2, '.', '') : null);
        $lure->setTienda(trim((string) $request->request->get('tienda', '')) ?: null);
        $lure->setPropietario(trim((string) $request->request->get('propietario', '')) ?: null);
        $lure->setNotas(trim((string) $request->request->get('notas', '')) ?: null);

        // Subida de foto
        $fotoFile = $request->files->get('foto');
        if ($fotoFile !== null) {
            $safeFilename = $this->slugger->slug($lure->getNombre());
            $filename     = $safeFilename . '-' . uniqid() . '.' . $fotoFile->guessExtension();
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fotoFile->move($uploadDir, $filename);
            $lure->setFoto($filename);
        }

        $em->persist($lure);
        $em->flush();

        $this->addFlash('success', sprintf('Señuelo "%s" agregado.', $lure->getNombre()));
        return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
    }

    // ── Resultado de señuelo en finca ─────────────────────────────────────────

    #[Route('/lure-result/create', name: 'lure_result_create', methods: ['POST'])]
    public function createLureResult(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('fishing_lure_result', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
        }

        $lure  = $this->lureRepo->find((int) $request->request->get('lure_id'));
        $finca = $this->fincaRepo->find((int) $request->request->get('finca_id'));

        if ($lure === null || $finca === null) {
            $this->addFlash('danger', 'Señuelo o finca no encontrados.');
            return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
        }

        $result = new FishingLureResult();
        $result->setLure($lure);
        $result->setFinca($finca);
        $result->setFunciono((bool) $request->request->get('funciono', true));
        $result->setNotas(trim((string) $request->request->get('notas', '')) ?: null);
        $result->setRegistradoPorUserId($this->getUser()?->getId());

        $em->persist($result);
        $em->flush();

        $this->addFlash('success', 'Resultado registrado.');

        $fincaId = $request->request->get('redirect_finca');
        return $fincaId
            ? $this->redirectToRoute('grova_fishing_finca_show', ['id' => $fincaId, '_locale' => $request->getLocale()])
            : $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
    }

    // ── Salidas de pesca ──────────────────────────────────────────────────────

    #[Route('/trip/create', name: 'trip_create', methods: ['POST'])]
    public function createTrip(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('fishing_trip', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
        }

        $finca = $this->fincaRepo->find((int) $request->request->get('finca_id'));
        if ($finca === null) {
            $this->addFlash('danger', 'Finca no encontrada.');
            return $this->redirectToRoute('grova_fishing_index', ['_locale' => $request->getLocale()]);
        }

        $trip = new FishingTrip();
        $trip->setFinca($finca);
        $trip->setFecha(new \DateTimeImmutable((string) $request->request->get('fecha', date('Y-m-d'))));
        $trip->setNotas(trim((string) $request->request->get('notas', '')) ?: null);

        // Agregar al usuario actual como miembro
        $user   = $this->getUser();
        $member = new FishingTripMember();
        $member->setTrip($trip);
        $member->setUserId($user->getId());
        $member->setNombre($user->getNombreCompleto() ?? $user->getUserIdentifier());
        $trip->getMembers()->add($member);

        $em->persist($trip);
        $em->persist($member);
        $em->flush();

        $this->addFlash('success', 'Salida registrada. Agrega gastos y señuelos.');
        return $this->redirectToRoute('grova_fishing_trip_show', ['id' => $trip->getId(), '_locale' => $request->getLocale()]);
    }

    #[Route('/trip/{id}', name: 'trip_show', methods: ['GET'])]
    public function showTrip(int $id): Response
    {
        $trip = $this->tripRepo->find($id);
        if ($trip === null) {
            throw $this->createNotFoundException();
        }

        $tree  = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $lures = $this->lureRepo->findAllOrdered();

        return $this->render('workspace/pages/fishing/trip.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'pesca',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'trip'                    => $trip,
            'lures'                   => $lures,
        ]);
    }

    #[Route('/trip/{id}/expense/add', name: 'trip_expense_add', methods: ['POST'])]
    public function addExpense(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('fishing_expense_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_fishing_trip_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $trip = $this->tripRepo->find($id);
        if ($trip === null) { throw $this->createNotFoundException(); }

        $expense = new FishingExpense();
        $expense->setTrip($trip);
        $expense->setConcepto(trim((string) $request->request->get('concepto', '')));
        $expense->setMonto(abs((float) $request->request->get('monto', 0)));
        $expense->setPagadoPor(trim((string) $request->request->get('pagado_por', '')) ?: null);

        $em->persist($expense);
        $em->flush();

        $this->addFlash('success', 'Gasto agregado.');
        return $this->redirectToRoute('grova_fishing_trip_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }

    #[Route('/trip/{id}/lure/add', name: 'trip_lure_add', methods: ['POST'])]
    public function addTripLure(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('fishing_trip_lure_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_fishing_trip_show', ['id' => $id, '_locale' => $request->getLocale()]);
        }

        $trip = $this->tripRepo->find($id);
        $lure = $this->lureRepo->find((int) $request->request->get('lure_id'));

        if ($trip === null || $lure === null) { throw $this->createNotFoundException(); }

        $tripLure = new FishingTripLure();
        $tripLure->setTrip($trip);
        $tripLure->setLure($lure);
        $tripLure->setFunciono((bool) $request->request->get('funciono', true));
        $tripLure->setNotas(trim((string) $request->request->get('notas', '')) ?: null);

        $em->persist($tripLure);
        $em->flush();

        $this->addFlash('success', 'Señuelo agregado a la salida.');
        return $this->redirectToRoute('grova_fishing_trip_show', ['id' => $id, '_locale' => $request->getLocale()]);
    }
}
