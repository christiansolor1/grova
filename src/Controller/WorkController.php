<?php

declare(strict_types=1);

namespace App\Controller;

use App\Module\Personal\Work\Entity\WorkDay;
use App\Module\Personal\Work\Entity\WorkVacation;
use App\Module\Personal\Work\Repository\WorkClientRepository;
use App\Module\Personal\Work\Repository\WorkDayRepository;
use App\Module\Personal\Work\Repository\WorkInvoiceRepository;
use App\Module\Personal\Work\Repository\WorkVacationRepository;
use App\Module\Personal\Work\Service\HondurasPublicHolidayCalculator;
use App\Service\ExchangeRateService;
use App\Service\MenuTreeBuilder;
use App\Service\NotificationService;
use App\Service\WorkInvoicePaymentProofStorage;
use App\Service\WorkInvoiceService;
use App\Module\Personal\Work\Entity\WorkClient;
use App\Module\Personal\Work\Entity\WorkInvoice;
use App\Module\Personal\Work\PublicHolidayEntry;
use App\Module\Personal\Work\WorkMonthLabel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/work', name: 'grova_work_')]
final class WorkController extends AbstractController
{
    public function __construct(
        private readonly MenuTreeBuilder $menuTreeBuilder,
        private readonly WorkClientRepository $clientRepo,
        private readonly WorkDayRepository $dayRepo,
        private readonly HondurasPublicHolidayCalculator $hnHolidayCalculator,
        private readonly WorkVacationRepository $vacationRepo,
        private readonly WorkInvoiceRepository $invoiceRepo,
        private readonly WorkInvoiceService $invoiceService,
        private readonly ExchangeRateService $exchangeRate,
        private readonly TranslatorInterface $translator,
        private readonly NotificationService $notificationService,
        private readonly WorkInvoicePaymentProofStorage $invoicePaymentProofStorage,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $now        = new \DateTimeImmutable();
        $year    = (int) ($request->query->get('year', $now->format('Y')));
        $month   = max(1, min(12, (int) ($request->query->get('month', $now->format('n')))));
        $holidayYear = (int) $request->query->get('holiday_year', (string) $year);
        $holidayYear = max(2020, min(2099, $holidayYear));
        $showAll = $request->query->getBoolean('show_all', false);
        $isCurrentMonth = $year === (int) $now->format('Y') && $month === (int) $now->format('n');

        $prevDate  = (new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month)))->modify('-1 month');
        $nextDate  = (new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month)))->modify('+1 month');
        $prevYear  = (int) $prevDate->format('Y');
        $prevMonth = (int) $prevDate->format('n');
        $nextYear  = (int) $nextDate->format('Y');
        $nextMonth = (int) $nextDate->format('n');

        $tree   = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $client = $this->clientRepo->findActivo();

        $daysInMonth    = $this->dayRepo->findByMonth($year, $month);
        $days           = $showAll ? $this->dayRepo->findAllSorted() : $daysInMonth;
        $distinctMonths = $this->dayRepo->findDistinctMonths();
        $distinctYears     = array_values(array_unique(array_column($distinctMonths, 'year')));
        $monthsForYear     = array_values(array_filter($distinctMonths, fn($m) => $m['year'] === $year));
        $locale            = $request->getLocale();
        $monthsForYear     = array_map(static function (array $row) use ($locale): array {
            $y  = (int) $row['year'];
            $mo = (int) $row['month'];

            return $row + ['month_label' => WorkMonthLabel::format($y, $mo, $locale)];
        }, $monthsForYear);
        $holidays = $this->hnHolidayCalculator->holidaysForYear($year);
        $holidaysCard = $this->hnHolidayCalculator->holidaysForYear($holidayYear);
        $holidayDaysInMonth = [];
        foreach ($holidays as $h) {
            $f = $h->getFecha();
            if ((int) $f->format('Y') === $year && (int) $f->format('n') === $month) {
                $d = (int) $f->format('j');
                $nombre = $h->getNombre();
                $holidayDaysInMonth[$d] = isset($holidayDaysInMonth[$d])
                    ? $holidayDaysInMonth[$d].' / '.$nombre
                    : $nombre;
            }
        }
        $invoiceDeadlineDom     = self::findBusinessDayBeforeLastBusinessDayOfMonth($year, $month, $holidays);
        $invoiceSentDomDetails  = [];
        $invoicePaidDomDetails  = [];
        if ($client !== null) {
            foreach ($this->invoiceRepo->findWithEnvioOrPagoInCalendarMonth($client, $year, $month) as $invCal) {
                $period = WorkMonthLabel::format($invCal->getAnio(), $invCal->getMes(), $locale);
                $num    = $invCal->getNumero();
                $numStr = $num !== null ? (string) $num : $this->translator->trans('work.cal_inv_num_missing', [], 'work');

                $ea = $invCal->getEnviadaAt();
                if ($ea !== null && (int) $ea->format('Y') === $year && (int) $ea->format('n') === $month) {
                    $d = (int) $ea->format('j');
                    $msg = $this->translator->trans('work.cal_mosaic_tip_inv_sent_detail', [
                        '%num%'    => $numStr,
                        '%period%' => $period,
                    ], 'work');
                    $invoiceSentDomDetails[$d] = isset($invoiceSentDomDetails[$d])
                        ? $invoiceSentDomDetails[$d].' | '.$msg
                        : $msg;
                }
                $pa = $invCal->getPagadaAt();
                if ($pa !== null && (int) $pa->format('Y') === $year && (int) $pa->format('n') === $month) {
                    $d = (int) $pa->format('j');
                    $msg = $this->translator->trans('work.cal_mosaic_tip_inv_paid_detail', [
                        '%num%'    => $numStr,
                        '%period%' => $period,
                    ], 'work');
                    $invoicePaidDomDetails[$d] = isset($invoicePaidDomDetails[$d])
                        ? $invoicePaidDomDetails[$d].' | '.$msg
                        : $msg;
                }
            }
        }
        $padInvoiceDomDetails = static function (array $byDom): array {
            return array_replace(array_fill_keys(range(1, 31), null), $byDom);
        };
        $invoiceSentDomDetails = $padInvoiceDomDetails($invoiceSentDomDetails);
        $invoicePaidDomDetails = $padInvoiceDomDetails($invoicePaidDomDetails);
        $nowYear       = (int) $now->format('Y');
        $invoiceYear   = (int) $request->query->get('invoice_year', (string) $nowYear);
        $invoiceYear   = max(2020, min(2099, $invoiceYear));
        $invoiceYears  = array_values(array_unique(array_merge(
            $this->invoiceRepo->findDistinctAnios(),
            [$nowYear, $invoiceYear],
        )));
        rsort($invoiceYears, SORT_NUMERIC);
        $invoices       = $this->invoiceRepo->findByAnio($invoiceYear);
        $recargoHnlPerPayment = $client ? $client->getRecargoHnlFloat() : 0.0;
        $invoiceSummary = [
            'count'                 => \count($invoices),
            'total_eur'             => 0.0,
            'received_eur'          => 0.0,
            'pending_payment_eur'   => 0.0,
            'paid_count'            => 0,
            'pending_payment_count' => 0,
            'sent_count'            => 0,
            'draft_count'           => 0,
        ];
        $commissionPaidSum = 0.0;
        foreach ($invoices as $inv) {
            $t = $inv->getTotalFloat();
            $invoiceSummary['total_eur'] += $t;
            if ($inv->isPagada()) {
                $invoiceSummary['received_eur'] += $t;
                ++$invoiceSummary['paid_count'];
                $commissionPaidSum += $inv->getEffectiveComisionBancoHnl($recargoHnlPerPayment);
            } else {
                $invoiceSummary['pending_payment_eur'] += $t;
                ++$invoiceSummary['pending_payment_count'];
            }
            if ($inv->isEnviada()) {
                ++$invoiceSummary['sent_count'];
            } else {
                ++$invoiceSummary['draft_count'];
            }
        }

        $invoiceSummary['recargo_hnl_per_payment'] = $recargoHnlPerPayment;
        $invoiceSummary['commission_hnl_paid_sum'] = round($commissionPaidSum, 2);
        $invoiceSummary['commission_hnl_pending_est'] = round($recargoHnlPerPayment * $invoiceSummary['pending_payment_count'], 2);
        $invoiceSummary['commission_hnl_if_all_invoices_paid'] = round($recargoHnlPerPayment * $invoiceSummary['count'], 2);
        $lockedMonths   = $client !== null ? $this->invoiceRepo->findLockedMonths($client) : [];
        $invoicedMonths = $client !== null ? $this->invoiceRepo->findInvoicedMonths($client) : [];
        $invoiceForViewMonth = ($client !== null)
            ? $this->invoiceRepo->findForMonth($client, $year, $month)
            : null;
        $workRecalcMonthHasInvoice = $invoiceForViewMonth !== null;
        $workRecalcMonthIsPaid     = $invoiceForViewMonth !== null && $invoiceForViewMonth->isPagada();
        $vacations     = $this->vacationRepo->findByYear($year);

        // Calendario del mes visto: mapa y contadores siempre con findByMonth (no mezclar "ver todo" con días 1–31)
        $firstDate  = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
        $firstDow   = (int) $firstDate->format('N'); // 1=Lun … 7=Dom
        $totalDays  = (int) $firstDate->format('t');
        $dayMap     = [];
        foreach ($daysInMonth as $day) {
            $dayMap[(int) $day->getFecha()->format('j')] = $day;
        }

        // Pie del calendario: feriado = mismo criterio que el mosaico (feriado nacional y/o flag en work_day)
        $diasTrabajados = 0;
        $diasBonus      = 0;
        $diasFeriados   = 0;
        $diasVacaciones = 0;

        if ($client !== null) {
            foreach ($daysInMonth as $day) {
                if ($day->trabajado()) {
                    ++$diasTrabajados;
                    if ($day->bonusAplica()) {
                        ++$diasBonus;
                    }
                }
                if ($day->isEsVacacion()) {
                    ++$diasVacaciones;
                }
            }
            for ($d = 1; $d <= $totalDays; ++$d) {
                $feriadoOficial = isset($holidayDaysInMonth[$d]);
                $wd             = $dayMap[$d] ?? null;
                if ($feriadoOficial || ($wd !== null && $wd->isEsFeriado())) {
                    ++$diasFeriados;
                }
            }
        } else {
            for ($d = 1; $d <= $totalDays; ++$d) {
                if (isset($holidayDaysInMonth[$d])) {
                    ++$diasFeriados;
                }
            }
        }

        $salarioBase  = $client ? $client->getSalarioBaseFloat() : 1100.0;
        $bonusDia     = $client ? $client->getBonusDiaFloat() : 12.5;
        $proyectado   = $salarioBase + ($diasBonus * $bonusDia);
        // Construir semanas: array de 6 semanas × 7 celdas (null = vacío)
        $calWeeks = [];
        $week     = array_fill(0, $firstDow - 1, null);
        for ($d = 1; $d <= $totalDays; $d++) {
            $week[] = $d;
            if (count($week) === 7) {
                $calWeeks[] = $week;
                $week = [];
            }
        }
        if (!empty($week)) {
            while (count($week) < 7) $week[] = null;
            $calWeeks[] = $week;
        }

        $rates         = $this->exchangeRate->getRates();
        $salaryFxRates = $this->ratesForSalaryCard($client, $rates);
        $salaryFxHint  = $this->buildSalaryFxHint($client, $locale);
        $recargoHnl    = $client ? $client->getRecargoHnlFloat() : 0.0;
        $totalHnl      = round($proyectado * (float) ($salaryFxRates['eur'] ?? 0), 2);
        $totalFinalHnl = round($totalHnl - $recargoHnl, 2);

        $eurRate = (float) ($rates['eur'] ?? 0);
        if ($eurRate > 0) {
            $receivedLGross = round($invoiceSummary['received_eur'] * $eurRate, 2);
            $pendingLGross  = round($invoiceSummary['pending_payment_eur'] * $eurRate, 2);
            $totalLGross    = round($invoiceSummary['total_eur'] * $eurRate, 2);
            $invoiceSummary['received_l_gross'] = $receivedLGross;
            $invoiceSummary['received_l_net'] = round($receivedLGross - $invoiceSummary['commission_hnl_paid_sum'], 2);
            $invoiceSummary['pending_l_gross'] = $pendingLGross;
            $invoiceSummary['pending_l_net_est'] = round($pendingLGross - $invoiceSummary['commission_hnl_pending_est'], 2);
            $invoiceSummary['total_l_gross'] = $totalLGross;
            $invoiceSummary['total_l_net_if_all_paid'] = round($totalLGross - $invoiceSummary['commission_hnl_if_all_invoices_paid'], 2);
            $invoiceSummary['received_eur_net'] = round(
                $invoiceSummary['received_eur'] - ($invoiceSummary['commission_hnl_paid_sum'] / $eurRate),
                2
            );
        } else {
            $invoiceSummary['received_l_gross'] = null;
            $invoiceSummary['received_l_net'] = null;
            $invoiceSummary['pending_l_gross'] = null;
            $invoiceSummary['pending_l_net_est'] = null;
            $invoiceSummary['total_l_gross'] = null;
            $invoiceSummary['total_l_net_if_all_paid'] = null;
            $invoiceSummary['received_eur_net'] = null;
        }

        // Vacaciones
        $vacUsadoS1 = $this->vacationRepo->countDaysUsedInSemestre($year, 1);
        $vacUsadoS2 = $this->vacationRepo->countDaysUsedInSemestre($year, 2);
        $vacTotal   = $vacUsadoS1 + $vacUsadoS2;

        $vigenteYear = (int) $now->format('Y');

        $workInvoiceMonthLabels = [];
        for ($mi = 1; $mi <= 12; ++$mi) {
            $workInvoiceMonthLabels[$mi] = WorkMonthLabel::format($vigenteYear, $mi, $locale);
        }

        $holidaysClipboardText = $this->buildHolidaysClipboardText($holidaysCard, $holidayYear, $locale);

        return $this->render('workspace/pages/work/indexWork.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'work',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'client'                  => $client,
            'days'                    => $days,
            'holidays'                => $holidays,
            'holidays_card'           => $holidaysCard,
            'holiday_year'            => $holidayYear,
            'work_cal_prev_query'     => $this->buildWorkIndexQuery($request, $prevYear, $prevMonth, $holidayYear, $showAll),
            'work_cal_next_query'     => $this->buildWorkIndexQuery($request, $nextYear, $nextMonth, $holidayYear, $showAll),
            'holiday_nav_prev_query'  => $holidayYear > 2020 ? $this->buildWorkIndexQuery($request, $year, $month, $holidayYear - 1, $showAll) : null,
            'holiday_nav_next_query'  => $holidayYear < 2099 ? $this->buildWorkIndexQuery($request, $year, $month, $holidayYear + 1, $showAll) : null,
            'holidays_clipboard_text' => $holidaysClipboardText,
            'holiday_days_in_month'   => $holidayDaysInMonth,
            'invoices'                => $invoices,
            'invoice_year'            => $invoiceYear,
            'invoice_years'           => $invoiceYears,
            'invoice_summary'         => $invoiceSummary,
            'locked_months'           => $lockedMonths,
            'invoiced_months'         => $invoicedMonths,
            'work_recalc_month_has_invoice' => $workRecalcMonthHasInvoice,
            'work_recalc_month_is_paid'     => $workRecalcMonthIsPaid,
            'invoice_for_view_month'        => $invoiceForViewMonth,
            'vacations'               => $vacations,
            'dias_trabajados'         => $diasTrabajados,
            'dias_bonus'              => $diasBonus,
            'dias_feriados'           => $diasFeriados,
            'dias_vacaciones'         => $diasVacaciones,
            'proyectado'              => $proyectado,
            'salario_base'            => $salarioBase,
            'bonus_dia'               => $bonusDia,
            'vac_usado_s1'            => $vacUsadoS1,
            'vac_usado_s2'            => $vacUsadoS2,
            'vac_total'               => $vacTotal,
            'mes_label'               => WorkMonthLabel::format($year, $month, $locale),
            'year'                    => $year,
            'month'                   => $month,
            'cal_weeks'               => $calWeeks,
            'cal_day_map'             => $dayMap,
            'is_current_month'        => $isCurrentMonth,
            'cal_today_dom'           => (int) $now->format('j'),
            'invoice_deadline_dom'    => $invoiceDeadlineDom,
            'invoice_sent_dom_details' => $invoiceSentDomDetails,
            'invoice_paid_dom_details' => $invoicePaidDomDetails,
            'show_all'                => $showAll,
            'distinct_months'         => $distinctMonths,
            'distinct_years'          => $distinctYears,
            'months_for_year'         => $monthsForYear,
            'prev_year'               => $prevYear,
            'prev_month'              => $prevMonth,
            'next_year'               => $nextYear,
            'next_month'              => $nextMonth,
            'rates'                   => $rates,
            'salary_fx_rates'         => $salaryFxRates,
            'salary_fx_hint'          => $salaryFxHint,
            'total_hnl'               => $totalHnl,
            'recargo_hnl'             => $recargoHnl,
            'total_final_hnl'         => $totalFinalHnl,
            'current_month_key'       => sprintf('%04d-%02d', $year, $month),
            'vigente_year'            => $vigenteYear,
            'work_invoice_month_labels' => $workInvoiceMonthLabels,
        ]);
    }

    #[Route('/day/create', name: 'day_create', methods: ['POST'])]
    public function createDay(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_day', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $client = $this->clientRepo->findActivo();
        if ($client === null) {
            $this->addFlash('danger', $this->translator->trans('work.flash_no_active_client_configured', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $fechaStr    = (string) $request->request->get('fecha', date('Y-m-d'));
        $horaEntrada = trim((string) $request->request->get('hora_entrada', ''));
        $esFeriado   = (bool) $request->request->get('es_feriado', false);
        $esVacacion  = (bool) $request->request->get('es_vacacion', false);
        $notas       = trim((string) $request->request->get('notas', ''));

        $fecha = new \DateTimeImmutable($fechaStr);
        if ($this->isMonthInvoiced((int) $fecha->format('Y'), (int) $fecha->format('n'), $client)) {
            $this->addFlash('danger', $this->translator->trans('work.flash_cannot_modify_days_invoiced_month', [], 'work'));
            return $this->redirectToRoute('grova_work_index', [
                '_locale' => $request->getLocale(),
                'year'    => (int) $fecha->format('Y'),
                'month'   => (int) $fecha->format('n'),
            ]);
        }

        // Verificar que no exista ya ese día
        $existing = $this->dayRepo->findOneBy(['fecha' => $fecha]);
        if ($existing !== null) {
            $this->addFlash('warning', $this->translator->trans('work.flash_day_exists_warning', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $day = new WorkDay();
        $day->setClient($client);
        $day->setFecha($fecha);
        $day->setHoraEntrada($horaEntrada !== '' ? $horaEntrada : null);
        $day->setEsFeriado($esFeriado);
        $day->setEsVacacion($esVacacion);
        $day->setNotas($notas !== '' ? $notas : null);

        $em->persist($day);
        $em->flush();

        $msg = $this->translator->trans('work.flash_day_created', [], 'work');
        if ($day->bonusAplica()) {
            $msg .= $this->translator->trans('work.flash_day_created_bonus_suffix', [
                '%amount%' => number_format($client->getBonusDiaFloat(), 2, '.', ''),
            ], 'work');
        }
        $this->addFlash('success', $msg);

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/day/{id}/edit', name: 'day_edit', methods: ['POST'])]
    public function editDay(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_day_edit_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $day = $this->dayRepo->find($id);
        if ($day === null) {
            throw $this->createNotFoundException();
        }

        $fechaStr    = (string) $request->request->get('fecha', $day->getFecha()->format('Y-m-d'));
        $horaEntrada = trim((string) $request->request->get('hora_entrada', ''));
        $esFeriado   = (bool) $request->request->get('es_feriado', false);
        $esVacacion  = (bool) $request->request->get('es_vacacion', false);
        $notas       = trim((string) $request->request->get('notas', ''));

        $client = $this->clientRepo->findActivo();
        $oldKeyY = (int) $day->getFecha()->format('Y');
        $oldKeyM = (int) $day->getFecha()->format('n');
        $newFecha = new \DateTimeImmutable($fechaStr);
        $newKeyY = (int) $newFecha->format('Y');
        $newKeyM = (int) $newFecha->format('n');
        if ($client !== null && (
            $this->isMonthInvoiced($oldKeyY, $oldKeyM, $client) ||
            $this->isMonthInvoiced($newKeyY, $newKeyM, $client)
        )) {
            $this->addFlash('danger', $this->translator->trans('work.flash_cannot_modify_days_invoiced_month', [], 'work'));
            return $this->redirectToRoute('grova_work_index', [
                '_locale' => $request->getLocale(),
                'year'    => $newKeyY,
                'month'   => $newKeyM,
            ]);
        }

        $day->setFecha($newFecha);
        $day->setHoraEntrada($horaEntrada !== '' ? $horaEntrada : null);
        $day->setEsFeriado($esFeriado);
        $day->setEsVacacion($esVacacion);
        $day->setNotas($notas !== '' ? $notas : null);

        $em->flush();
        $this->addFlash('success', $this->translator->trans('work.flash_day_updated', [], 'work'));

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/day/{id}/delete', name: 'day_delete', methods: ['POST'])]
    public function deleteDay(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_day_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $day = $this->dayRepo->find($id);
        if ($day !== null) {
            $client = $this->clientRepo->findActivo();
            if ($client !== null && $this->isMonthInvoiced((int) $day->getFecha()->format('Y'), (int) $day->getFecha()->format('n'), $client)) {
                $this->addFlash('danger', $this->translator->trans('work.flash_cannot_delete_days_invoiced_month', [], 'work'));
                return $this->redirectToRoute('grova_work_index', [
                    '_locale' => $request->getLocale(),
                    'year'    => (int) $day->getFecha()->format('Y'),
                    'month'   => (int) $day->getFecha()->format('n'),
                ]);
            }

            $em->remove($day);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('work.flash_day_deleted', [], 'work'));
        }

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/clients', name: 'clients', methods: ['GET'])]
    public function clients(): Response
    {
        $tree    = $this->menuTreeBuilder->buildTree($this->isGranted('ROLE_DEVELOPER'));
        $clients = $this->clientRepo->findAllOrdered();

        return $this->render('workspace/pages/work/clients.html.twig', [
            'menu_tree'               => $tree,
            'active_menu_key'         => 'work',
            'workspace_home_menu_key' => MenuTreeBuilder::HOME_MENU_KEY,
            'clients'                 => $clients,
        ]);
    }

    #[Route('/client/create', name: 'client_create', methods: ['POST'])]
    public function createClient(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_client', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
        }

        $client = new \App\Module\Personal\Work\Entity\WorkClient();
        $this->fillClientFromRequest($client, $request);

        $em->persist($client);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('work.flash_client_created', [], 'work'));
        return $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
    }

    #[Route('/client/{id}/edit', name: 'client_edit', methods: ['POST'])]
    public function editClient(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_client_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
        }

        $client = $this->clientRepo->find($id);
        if ($client === null) {
            $this->addFlash('danger', $this->translator->trans('work.flash_client_not_found', [], 'work'));
            return $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
        }

        $this->fillClientFromRequest($client, $request);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('work.flash_client_updated', [], 'work'));

        $redirect = $request->request->get('_redirect', 'clients');
        return $redirect === 'index'
            ? $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()])
            : $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
    }

    #[Route('/client/{id}/activate', name: 'client_activate', methods: ['POST'])]
    public function activateClient(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_client_activate_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
        }

        foreach ($this->clientRepo->findAll() as $c) {
            $c->setActivo(false);
        }

        $client = $this->clientRepo->find($id);
        if ($client !== null) {
            $client->setActivo(true);
            $this->addFlash('success', $this->translator->trans('work.flash_client_now_active', ['%name%' => $client->getNombre()], 'work'));
        }

        $em->flush();
        return $this->redirectToRoute('grova_work_clients', ['_locale' => $request->getLocale()]);
    }

    #[Route('/recalculate', name: 'recalculate', methods: ['POST'])]
    public function recalculate(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('work_recalculate', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $year  = (int) $request->request->get('year', date('Y'));
        $month = max(1, min(12, (int) $request->request->get('month', date('n'))));

        $client = $this->clientRepo->findActivo();
        if ($client === null) {
            $this->addFlash('danger', $this->translator->trans('work.flash_no_active_client', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        if ($year !== $this->vigenteYear()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_invoice_year_restriction', [], 'work'));
            return $this->redirectToRoute('grova_work_index', [
                '_locale' => $request->getLocale(),
                'year'    => $year,
                'month'   => $month,
            ]);
        }

        $existing = $this->invoiceRepo->findForMonth($client, $year, $month);
        if ($existing === null) {
            $this->addFlash('warning', $this->translator->trans('work.flash_recalc_no_invoice', [], 'work'));

            return $this->redirectToRoute('grova_work_index', [
                '_locale' => $request->getLocale(),
                'year'    => $year,
                'month'   => $month,
            ]);
        }
        if ($existing->isPagada()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_recalc_blocked_paid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', [
                '_locale' => $request->getLocale(),
                'year'    => $year,
                'month'   => $month,
            ]);
        }

        $invoice    = $this->invoiceService->generateForMonth($client, $year, $month);
        $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $this->notificationService->notify(
            user: $user,
            title: 'Factura recalculada — ' . $monthLabel,
            body: $invoice->getDiasTrabajados() . ' días · ' . $invoice->getDiasBonus() . ' bonus · €' . number_format($invoice->getTotalFloat(), 2, ',', '.'),
            url: '/' . $request->getLocale() . '/work',
            module: 'work',
            icon: 'bi bi-arrow-repeat',
            type: 'info',
        );

        $this->addFlash('success', $this->translator->trans('work.flash_invoice_recalculated', [
            '%month%' => $monthLabel,
            '%days%' => (string) $invoice->getDiasTrabajados(),
            '%bonus%' => (string) $invoice->getDiasBonus(),
            '%total%' => number_format($invoice->getTotalFloat(), 2, '.', ''),
        ], 'work'));

        return $this->redirectToRoute('grova_work_index', [
            '_locale' => $request->getLocale(),
            'year'    => $year,
            'month'   => $month,
        ]);
    }

    #[Route('/invoice/generate', name: 'invoice_generate', methods: ['POST'])]
    public function generateInvoice(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('work_invoice_generate', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $client = $this->clientRepo->findActivo();
        if ($client === null) {
            $this->addFlash('danger', $this->translator->trans('work.flash_no_active_client', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $year  = (int) $request->request->get('year', date('Y'));
        $month = (int) $request->request->get('month', date('n'));

        if ($year !== $this->vigenteYear()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_invoice_year_restriction', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $existing = $this->invoiceRepo->findForMonth($client, $year, $month);
        if ($existing !== null && $existing->isPagada()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_invoice_generated_blocked_paid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $invoice    = $this->invoiceService->generateForMonth($client, $year, $month);
        $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());
        $this->addFlash('success', $this->translator->trans('work.flash_invoice_generated', [
            '%month%' => $monthLabel,
            '%total%' => number_format($invoice->getTotalFloat(), 2, '.', ''),
        ], 'work'));

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $this->notificationService->notify(
            user: $user,
            title: 'Factura generada — ' . $monthLabel,
            body: 'Total: €' . number_format($invoice->getTotalFloat(), 2, ',', '.'),
            url: '/' . $request->getLocale() . '/work',
            module: 'work',
            icon: 'bi bi-receipt',
            type: 'info',
        );

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/invoice/{id}/pdf', name: 'invoice_pdf', methods: ['GET'])]
    public function downloadInvoicePdf(int $id): Response
    {
        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        $pdf      = $this->invoiceService->generatePdf($invoice);
        $prefix   = $this->translator->trans('work.pdf_filename_invoice_prefix', [], 'work');
        $filename = sprintf('%s_%s_%s.pdf', $prefix, $invoice->getClient()->getNombre(), date('Y_m', mktime(0, 0, 0, $invoice->getMes(), 1, $invoice->getAnio())));

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('/invoice/{id}/pdf/external', name: 'invoice_pdf_external', methods: ['GET'])]
    public function downloadInvoicePdfExternal(int $id): Response
    {
        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        $pdf      = $this->invoiceService->generatePdfExternal($invoice);
        $prefix   = $this->translator->trans('work.pdf_filename_receipt_prefix', [], 'work');
        $filename = sprintf('%s_%s_%s.pdf', $prefix, $invoice->getNumero() ?? (string) $invoice->getId(), date('Y_m', mktime(0, 0, 0, $invoice->getMes(), 1, $invoice->getAnio())));

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }

    #[Route('/invoice/{id}/send', name: 'invoice_send', methods: ['POST'])]
    public function sendInvoice(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('work_invoice_send_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        if ($invoice->isPagada()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_cannot_send_paid_invoice', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        try {
            $fromEmail = $this->getParameter('mailer_from_email');
            $this->invoiceService->sendByEmail($invoice, $fromEmail);
            $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());
            $this->addFlash('success', $this->translator->trans('work.flash_invoice_sent', [
                '%month%' => $monthLabel,
                '%emails%' => $invoice->getClient()->getEmailsFactura(),
            ], 'work'));

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->notificationService->notify(
                user: $user,
                title: 'Factura enviada — ' . $monthLabel,
                body: 'Enviada a ' . $invoice->getClient()->getEmailsFactura(),
                url: '/' . $request->getLocale() . '/work',
                module: 'work',
                icon: 'bi bi-send',
                type: 'info',
            );
        } catch (\Throwable $e) {
            $this->addFlash('danger', $this->translator->trans('work.flash_email_send_failed', ['%message%' => $e->getMessage()], 'work'));
        }

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/invoice/{id}/delete', name: 'invoice_delete', methods: ['POST'])]
    public function deleteInvoice(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_invoice_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $invoice = $this->invoiceRepo->find($id);
        if ($invoice !== null) {
            if ($invoice->getAnio() < $this->vigenteYear()) {
                $this->addFlash('danger', $this->translator->trans('work.err_invoice_delete_past_year', [], 'work'));
                return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
            }

            if ($invoice->isPagada()) {
                $this->addFlash('danger', $this->translator->trans('work.flash_cannot_delete_paid_invoice', [], 'work'));
                return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
            }

            $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());
            $this->invoicePaymentProofStorage->removeProof($invoice);
            $em->remove($invoice);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('work.flash_invoice_deleted', [], 'work'));

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $this->notificationService->notify(
                user: $user,
                title: 'Factura eliminada — ' . $monthLabel,
                body: 'La factura de ' . $monthLabel . ' fue eliminada.',
                url: '/' . $request->getLocale() . '/work',
                module: 'work',
                icon: 'bi bi-trash',
                type: 'danger',
            );
        }

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }

    #[Route('/invoice/{id}/manual-bonus', name: 'invoice_manual_bonus', methods: ['GET', 'POST'])]
    public function updateInvoiceManualBonus(int $id, Request $request): Response
    {
        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        if (!$request->isMethod('POST')) {
            $this->addFlash('info', $this->translator->trans('work.flash_manual_bonus_get_redirect', [], 'work'));

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        if (!$this->isCsrfTokenValid('work_invoice_manual_bonus_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));

            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        if ($invoice->isPagada()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_invoice_manual_bonus_blocked_paid', [], 'work'));

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        if ($invoice->getAnio() !== $this->vigenteYear()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_invoice_manual_bonus_year', [], 'work'));

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        $linesRaw = $this->parseExtraBonusLinesFromRequest($request);
        $this->invoiceService->replaceExtraBonusLines($invoice, $linesRaw);

        $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());
        $withAmount = array_values(array_filter($linesRaw, static fn (array $r): bool => ($r['eur'] ?? 0) > 0));
        $lineCount = \count($withAmount);
        $sumBono     = $invoice->getExtraBonusSumFloat();
        $this->addFlash('success', $this->translator->trans('work.flash_invoice_manual_bonus_updated', [
            '%month%'  => $monthLabel,
            '%bono%'   => number_format($sumBono, 2, '.', ''),
            '%total%'  => number_format($invoice->getTotalFloat(), 2, '.', ''),
            '%lines%'  => (string) $lineCount,
        ], 'work'));

        return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
    }

    #[Route('/invoice/{id}/paid', name: 'invoice_paid', methods: ['POST'])]
    public function toggleInvoicePaid(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_invoice_paid_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        if ($invoice->getAnio() !== $this->vigenteYear()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_paid_toggle_year', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($invoice->isPagada()) {
            $this->invoicePaymentProofStorage->removeProof($invoice);
            $invoice->setPagadaAt(null);
            $invoice->setComisionBancoHnl(null);
            $this->clearInvoicePaymentFxFields($invoice);
            $this->addFlash('success', $this->translator->trans('work.flash_invoice_marked_unpaid', ['%month%' => $monthLabel], 'work'));
            $this->notificationService->notify(
                user: $user,
                title: 'Factura revertida a pendiente — ' . $monthLabel,
                body: '€' . number_format($invoice->getTotalFloat(), 2, ',', '.') . ' marcados como pendientes de cobro.',
                url: '/' . $request->getLocale() . '/work',
                module: 'work',
                icon: 'bi bi-arrow-counterclockwise',
                type: 'warning',
            );
        } else {
            $fechaCobro = $this->parseFechaCobroParaPagada((string) $request->request->get('fecha_cobro', ''));
            if ($fechaCobro === null) {
                $this->addFlash('danger', $this->translator->trans('work.flash_invoice_paid_date_invalid', [], 'work'));
                return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
            }

            $rawComision = $request->request->get('comision_banco_hnl');
            $defaultL   = $invoice->getClient()->getRecargoHnlFloat();
            if ($rawComision === null || $rawComision === '') {
                $comisionL = $defaultL;
            } else {
                $comisionL = max(0.0, (float) $rawComision);
            }
            $invoice->setComisionBancoHnl(number_format($comisionL, 2, '.', ''));
            $invoice->setPagadaAt($fechaCobro);
            $invoice->setReciboSwift($this->parseReciboSwift((string) $request->request->get('recibo_swift', '')));

            // Si el usuario ingresó cuántos Lempiras recibió, calcular tasa efectiva
            $rawLempiras = $request->request->get('lempiras_recibidos');
            if ($rawLempiras !== null && $rawLempiras !== '') {
                $lempirasL = max(0.0, (float) $rawLempiras);
                $totalEur  = $invoice->getTotalFloat();
                $invoice->setLempirasRecibidos(number_format($lempirasL, 2, '.', ''));
                if ($totalEur > 0) {
                    $tasaEfectiva = ($lempirasL + $comisionL) / $totalEur;
                    $invoice->setTasaPagoEurL(number_format($tasaEfectiva, 5, '.', ''));
                    $invoice->setTasaPagoFecha(date('d/m/Y'));
                    $invoice->setTasaPagoSource('real (ingresado)');
                }
            } else {
                $this->applyPaymentFxSnapshotToInvoice($invoice);
            }
            $this->addFlash('success', $this->translator->trans('work.flash_invoice_marked_paid', ['%month%' => $monthLabel], 'work'));
            $this->notificationService->notify(
                user: $user,
                title: 'Factura cobrada — ' . $monthLabel,
                body: '€' . number_format($invoice->getTotalFloat(), 2, ',', '.') . ' recibidos de ' . $invoice->getClient()->getNombre(),
                url: '/' . $request->getLocale() . '/work',
                module: 'work',
                icon: 'bi bi-check-circle',
                type: 'success',
            );
        }

        $em->flush();

        return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
    }

    #[Route('/invoice/{id}/commission', name: 'invoice_commission', methods: ['POST'])]
    public function updateInvoiceBankCommission(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_invoice_commission_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        if (!$invoice->isPagada()) {
            $this->addFlash('danger', $this->translator->trans('work.flash_commission_only_paid', [], 'work'));
            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        $rawFecha = trim((string) $request->request->get('fecha_cobro', ''));
        if ($rawFecha === '') {
            $this->addFlash('warning', $this->translator->trans('work.flash_commission_date_required', [], 'work'));
            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        $fechaCobro = $this->parseFechaCobroParaPagada($rawFecha);
        if ($fechaCobro === null) {
            $this->addFlash('danger', $this->translator->trans('work.flash_invoice_paid_date_invalid', [], 'work'));
            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        $raw = $request->request->get('comision_banco_hnl');
        if ($raw === null || $raw === '') {
            $this->addFlash('warning', $this->translator->trans('work.flash_commission_empty', [], 'work'));
            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        $comisionL = max(0.0, (float) $raw);
        $invoice->setComisionBancoHnl(number_format($comisionL, 2, '.', ''));
        $invoice->setPagadaAt($fechaCobro);
        $rawRecibo = trim((string) $request->request->get('recibo_swift', ''));
        if ($rawRecibo !== '') {
            $invoice->setReciboSwift($this->parseReciboSwift($rawRecibo));
        }

        $rawLempiras = $request->request->get('lempiras_recibidos');
        if ($rawLempiras !== null && $rawLempiras !== '') {
            $lempirasL = max(0.0, (float) $rawLempiras);
            $totalEur  = $invoice->getTotalFloat();
            $invoice->setLempirasRecibidos(number_format($lempirasL, 2, '.', ''));
            if ($totalEur > 0) {
                $tasaEfectiva = ($lempirasL + $comisionL) / $totalEur;
                $invoice->setTasaPagoEurL(number_format($tasaEfectiva, 5, '.', ''));
                $invoice->setTasaPagoFecha(date('d/m/Y'));
                $invoice->setTasaPagoSource('real (ingresado)');
            }
        } else {
            $this->applyPaymentFxSnapshotToInvoice($invoice);
        }
        $em->flush();

        $monthLabel = WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $request->getLocale());
        $this->addFlash('success', $this->translator->trans('work.flash_invoice_payment_details_updated', ['%month%' => $monthLabel], 'work'));

        return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
    }

    /**
     * GET: ver comprobante de giro (PDF/imagen). POST: sustituir comprobante (solo factura ya pagada, año vigente).
     */
    #[Route('/invoice/{id}/payment-proof', name: 'invoice_payment_proof', methods: ['GET', 'POST'])]
    public function invoicePaymentProof(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            $wantsJson = $this->wantsWorkPaymentProofJson($request);
            $invoiceYear = $this->normalizeInvoiceYearFromRequest($request, $invoice);

            if (!$this->isCsrfTokenValid('work_invoice_payment_proof_' . $id, (string) $request->request->get('_token'))) {
                $msg = $this->translator->trans('work.flash_token_invalid', [], 'work');
                if ($wantsJson) {
                    return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_FORBIDDEN);
                }
                $this->addFlash('danger', $msg);

                return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
            }
            if (!$invoice->isPagada()) {
                $msg = $this->translator->trans('work.flash_payment_proof_only_paid', [], 'work');
                if ($wantsJson) {
                    return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $this->addFlash('danger', $msg);

                return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
            }
            if ($invoice->getAnio() !== $this->vigenteYear()) {
                $msg = $this->translator->trans('work.flash_paid_toggle_year', [], 'work');
                if ($wantsJson) {
                    return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $this->addFlash('danger', $msg);

                return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
            }

            $uploaded = $request->files->get('payment_proof');
            if (!$uploaded instanceof UploadedFile || $uploaded->getClientOriginalName() === '') {
                $msg = $this->translator->trans('work.flash_payment_proof_missing_file', [], 'work');
                if ($wantsJson) {
                    return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $this->addFlash('warning', $msg);

                return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
            }

            $err = $this->invoicePaymentProofStorage->tryReplaceProof(
                $invoice,
                $uploaded,
            );
            if ($err !== null) {
                $msg = $this->translator->trans($err, [], 'work');
                if ($wantsJson) {
                    return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $this->addFlash('danger', $msg);

                return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
            }

            $em->flush();
            $msg = $this->translator->trans('work.flash_payment_proof_updated', [], 'work');
            if ($wantsJson) {
                return new JsonResponse([
                    'success'   => true,
                    'message'   => $msg,
                    'invoiceId' => $invoice->getId(),
                    'cellHtml'  => $this->renderInvoiceProofCellFragment($request, $invoice, $invoiceYear),
                ]);
            }
            $this->addFlash('success', $msg);

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        if (!$invoice->hasPaymentProof()) {
            throw $this->createNotFoundException();
        }

        $path = $this->invoicePaymentProofStorage->absolutePath($invoice);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        $dispName = $invoice->getPaymentProofOriginalName() ?: 'comprobante';
        $fallback = 'comprobante.' . (pathinfo($path, PATHINFO_EXTENSION) ?: 'bin');

        return new BinaryFileResponse($path, Response::HTTP_OK, [
            'Content-Type' => $invoice->getPaymentProofMime() ?? 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $dispName,
                $fallback,
            ),
        ]);
    }

    #[Route('/invoice/{id}/payment-proof/delete', name: 'invoice_payment_proof_delete', methods: ['POST'])]
    public function deleteInvoicePaymentProof(
        int $id,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $wantsJson = $this->wantsWorkPaymentProofJson($request);

        if (!$this->isCsrfTokenValid('work_invoice_payment_proof_delete_' . $id, (string) $request->request->get('_token'))) {
            $msg = $this->translator->trans('work.flash_token_invalid', [], 'work');
            if ($wantsJson) {
                return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('danger', $msg);

            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $invoice = $this->invoiceRepo->find($id);
        if ($invoice === null) {
            throw $this->createNotFoundException();
        }

        $invoiceYear = $this->normalizeInvoiceYearFromRequest($request, $invoice);

        if (!$invoice->isPagada()) {
            $msg = $this->translator->trans('work.flash_payment_proof_only_paid', [], 'work');
            if ($wantsJson) {
                return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->addFlash('danger', $msg);

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        if ($invoice->getAnio() !== $this->vigenteYear()) {
            $msg = $this->translator->trans('work.flash_paid_toggle_year', [], 'work');
            if ($wantsJson) {
                return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->addFlash('danger', $msg);

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        if (!$invoice->hasPaymentProof()) {
            $msg = $this->translator->trans('work.flash_payment_proof_delete_none', [], 'work');
            if ($wantsJson) {
                return new JsonResponse(['success' => false, 'message' => $msg], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $this->addFlash('warning', $msg);

            return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
        }

        $this->invoicePaymentProofStorage->removeProof($invoice);
        $em->flush();
        $msg = $this->translator->trans('work.flash_payment_proof_deleted', [], 'work');
        if ($wantsJson) {
            return new JsonResponse([
                'success'   => true,
                'message'   => $msg,
                'invoiceId' => $invoice->getId(),
                'cellHtml'  => $this->renderInvoiceProofCellFragment($request, $invoice, $invoiceYear),
            ]);
        }
        $this->addFlash('success', $msg);

        return $this->redirectWorkIndexPreservingInvoiceYear($request, $invoice);
    }

    private function wantsWorkPaymentProofJson(Request $request): bool
    {
        if ($request->getPreferredFormat() === 'json') {
            return true;
        }

        $accept = (string) $request->headers->get('Accept', '');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
    }

    private function normalizeInvoiceYearFromRequest(Request $request, WorkInvoice $invoice): int
    {
        $y = (int) $request->request->get('invoice_year', $invoice->getAnio());
        if ($y < 2020 || $y > 2099) {
            $y = $invoice->getAnio();
        }

        return $y;
    }

    private function renderInvoiceProofCellFragment(Request $request, WorkInvoice $invoice, int $invoiceYear): string
    {
        return $this->renderView('workspace/pages/work/_invoice_proof_cell.html.twig', [
            'invoice'       => $invoice,
            'invoice_year'  => $invoiceYear,
            'locale'        => $request->getLocale(),
            'vigente_year'  => $this->vigenteYear(),
        ]);
    }

    private function redirectWorkIndexPreservingInvoiceYear(Request $request, WorkInvoice $invoice): Response
    {
        $y = (int) $request->request->get('invoice_year', 0);
        if ($y < 2020 || $y > 2099) {
            $y = $invoice->getAnio();
        }

        return $this->redirectToRoute('grova_work_index', [
            '_locale'      => $request->getLocale(),
            'invoice_year' => $y,
        ]);
    }

    /**
     * @return list<array{eur: float, concepto: string}>
     */
    private function parseExtraBonusLinesFromRequest(Request $request): array
    {
        $all  = $request->request->all();
        $eurs = $this->normalizeArrayishRequestValue($all['bono_line_eur'] ?? null);
        $cons = $this->normalizeArrayishRequestValue($all['bono_line_concepto'] ?? null);
        $max  = max(\count($eurs), \count($cons));
        $out  = [];
        for ($i = 0; $i < $max && $i < 40; ++$i) {
            $raw = isset($eurs[$i]) ? trim((string) $eurs[$i]) : '';
            $raw = str_replace([' ', "\xc2\xa0"], '', $raw);
            $raw = str_replace(',', '.', $raw);
            $eur = max(0.0, round((float) $raw, 2));
            $concepto = isset($cons[$i]) ? trim((string) $cons[$i]) : '';
            $out[]    = ['eur' => $eur, 'concepto' => $concepto];
        }

        return $out;
    }

    /**
     * @return list<mixed>
     */
    private function normalizeArrayishRequestValue(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return \is_array($value) ? array_values($value) : [$value];
    }

    /**
     * Fecha contable del cobro (día en que ingresó o lo consideras pagado). Vacío = hoy. No fechas futuras.
     * Hora fijada al mediodía en la zona por defecto de PHP para persistencia estable.
     */
    private function parseFechaCobroParaPagada(string $raw): ?\DateTimeImmutable
    {
        $tz    = new \DateTimeZone(date_default_timezone_get());
        $today = new \DateTimeImmutable('today', $tz);
        $trim  = trim($raw);
        if ($trim === '') {
            return $today->setTime(12, 0, 0);
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $trim, $tz);
        if ($dt === false) {
            return null;
        }

        if ($dt->format('Y-m-d') > $today->format('Y-m-d')) {
            return null;
        }

        return $dt->setTime(12, 0, 0);
    }

    private function ratesForSalaryCard(?WorkClient $client, array $live): array
    {
        if ($client === null) {
            return $live;
        }
        $inv = $this->invoiceRepo->findLatestWithEmissionRates($client);
        if ($inv === null) {
            return $live;
        }
        $eur = $inv->getTasaEmisionEurLFloat();
        if ($eur <= 0.0) {
            return $live;
        }
        $usd = $inv->getTasaEmisionUsdLFloat();
        if ($usd <= 0.0) {
            $usd = (float) ($live['usd'] ?? 0.0);
        }
        $out            = $live;
        $out['eur']     = $eur;
        $out['usd']     = $usd;
        $out['fecha']   = $inv->getTasaEmisionFecha() ?? ($live['fecha'] ?? '');
        $out['source']  = $inv->getTasaEmisionSource() ?? ($live['source'] ?? '');

        return $out;
    }

    private function buildSalaryFxHint(?WorkClient $client, string $locale): ?string
    {
        if ($client === null) {
            return null;
        }
        $inv = $this->invoiceRepo->findLatestWithEmissionRates($client);
        if ($inv === null || $inv->getTasaEmisionEurLFloat() <= 0) {
            return null;
        }

        return $this->translator->trans('work.salary_fx_from_invoice', [
            '%fecha%'  => $inv->getTasaEmisionFecha() ?? '—',
            '%period%' => WorkMonthLabel::format($inv->getAnio(), $inv->getMes(), $locale),
        ], 'work');
    }

    private function parseReciboSwift(string $raw): ?int
    {
        $t = trim($raw);
        if ($t === '') {
            return null;
        }
        if (!preg_match('/^\d+$/', $t)) {
            return null;
        }
        $n = (int) $t;

        return $n > 0 ? $n : null;
    }

    private function applyPaymentFxSnapshotToInvoice(WorkInvoice $invoice): void
    {
        $r = $this->exchangeRate->getRates();
        $eur = (float) ($r['eur'] ?? 0);
        if ($eur <= 0) {
            return;
        }
        $usd = (float) ($r['usd'] ?? 0);
        $invoice->setTasaPagoEurL(number_format($eur, 5, '.', ''));
        $invoice->setTasaPagoUsdL($usd > 0 ? number_format($usd, 5, '.', '') : null);
        $fecha = isset($r['fecha']) ? (string) $r['fecha'] : '';
        $invoice->setTasaPagoFecha($fecha !== '' ? mb_substr($fecha, 0, 16) : null);
        $src = isset($r['source']) ? (string) $r['source'] : '';
        $invoice->setTasaPagoSource($src !== '' ? mb_substr($src, 0, 64) : null);
    }

    private function clearInvoicePaymentFxFields(WorkInvoice $invoice): void
    {
        $invoice->setReciboSwift(null);
        $invoice->setLempirasRecibidos(null);
        $invoice->setTasaPagoEurL(null);
        $invoice->setTasaPagoUsdL(null);
        $invoice->setTasaPagoFecha(null);
        $invoice->setTasaPagoSource(null);
    }

    private function isMonthLocked(int $year, int $month, WorkClient $client): bool
    {
        $invoice = $this->invoiceRepo->findForMonth($client, $year, $month);
        return $invoice !== null && $invoice->isPagada();
    }

    private function isMonthInvoiced(int $year, int $month, WorkClient $client): bool
    {
        return $this->invoiceRepo->findForMonth($client, $year, $month) !== null;
    }

    /**
     * Último día hábil anterior al último día hábil del mes (lun–vie, sin feriados work_holiday).
     * Sirve para enviar factura un día hábil antes del día en que suele pagarse el mes.
     *
     * @param iterable<int, object{getFecha(): \DateTimeImmutable}> $holidaysOfYear
     */
    private static function findBusinessDayBeforeLastBusinessDayOfMonth(int $year, int $month, iterable $holidaysOfYear): ?int
    {
        $first = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $lastJ = (int) $first->modify('last day of this month')->format('j');

        $holidayYmd = [];
        foreach ($holidaysOfYear as $h) {
            $f = $h->getFecha();
            if ((int) $f->format('Y') === $year && (int) $f->format('n') === $month) {
                $holidayYmd[$f->format('Y-m-d')] = true;
            }
        }

        $isBusiness = static function (\DateTimeImmutable $dt) use ($holidayYmd): bool {
            if ((int) $dt->format('N') >= 6) {
                return false;
            }

            return !isset($holidayYmd[$dt->format('Y-m-d')]);
        };

        $lastBusinessDom = null;
        for ($d = $lastJ; $d >= 1; --$d) {
            $dt = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $d));
            if ($isBusiness($dt)) {
                $lastBusinessDom = $d;
                break;
            }
        }

        if ($lastBusinessDom === null || $lastBusinessDom <= 1) {
            return null;
        }

        for ($d = $lastBusinessDom - 1; $d >= 1; --$d) {
            $dt = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $d));
            if ($isBusiness($dt)) {
                return $d;
            }
        }

        return null;
    }

    private function vigenteYear(): int
    {
        return (int) (new \DateTimeImmutable('now'))->format('Y');
    }

    /**
     * Parámetros GET para grova_work_index (conserva invoice_year, show_all y holiday_year del card feriados).
     *
     * @return array<string, int|string>
     */
    private function buildWorkIndexQuery(Request $request, int $y, int $m, int $holidayY, bool $showAllFlag): array
    {
        $q = [
            'year'         => $y,
            'month'        => $m,
            'holiday_year' => $holidayY,
        ];
        if ($showAllFlag) {
            $q['show_all'] = 1;
        }
        $iy = $request->query->get('invoice_year');
        if ($iy !== null && $iy !== '' && is_numeric($iy)) {
            $iyInt = (int) $iy;
            if ($iyInt >= 2020 && $iyInt <= 2099) {
                $q['invoice_year'] = $iyInt;
            }
        }

        return $q;
    }

    /**
     * Texto plano para copiar/pegar el listado de feriados del año mostrado en el card.
     *
     * @param list<PublicHolidayEntry> $entries
     */
    private function buildHolidaysClipboardText(array $entries, int $holidayYear, string $locale): string
    {
        $lines   = [];
        $lines[] = $locale === 'es'
            ? sprintf('Feriados Honduras %d', $holidayYear)
            : sprintf('Honduras public holidays %d', $holidayYear);
        $lines[] = '';

        $wdFmt = null;
        if (\class_exists(\IntlDateFormatter::class)) {
            $cal   = \IntlDateFormatter::GREGORIAN;
            $wdFmt = new \IntlDateFormatter(
                $locale === 'es' ? 'es_HN' : 'en_US',
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                null,
                $cal,
                'EEEE',
            );
        }

        foreach ($entries as $h) {
            if (!$h instanceof PublicHolidayEntry) {
                continue;
            }
            $d   = $h->getFecha();
            $dmY = $d->format('d/m/Y');
            $nom = $h->getNombre();
            if ($wdFmt instanceof \IntlDateFormatter) {
                $dayName = (string) $wdFmt->format($d);
                $lines[] = sprintf('%s (%s): %s', $dmY, $dayName, $nom);
            } else {
                $lines[] = sprintf('%s: %s', $dmY, $nom);
            }
        }

        return implode("\n", $lines);
    }

    private function fillClientFromRequest(\App\Module\Personal\Work\Entity\WorkClient $client, Request $request): void
    {
        $client->setNombre(trim((string) $request->request->get('nombre', '')));
        $client->setCifNif(trim((string) $request->request->get('cif_nif', '')) ?: null);
        $client->setDireccion(trim((string) $request->request->get('direccion', '')) ?: null);
        $client->setEmailsFactura(trim((string) $request->request->get('emails_factura', '')));
        $client->setSalarioBase(number_format(abs((float) $request->request->get('salario_base', '1100')), 2, '.', ''));
        $client->setBonusDia(number_format(abs((float) $request->request->get('bonus_dia', '12.50')), 2, '.', ''));
        $client->setHoraLimiteBonus((string) $request->request->get('hora_limite_bonus', '08:00'));
        $client->setBancNombre((string) $request->request->get('banc_nombre', '') ?: null);
        $client->setBancDireccion((string) $request->request->get('banc_direccion', '') ?: null);
        $client->setBancSwift((string) $request->request->get('banc_swift', '') ?: null);
        $client->setBancCuenta((string) $request->request->get('banc_cuenta', '') ?: null);
        $client->setBancTitular((string) $request->request->get('banc_titular', '') ?: null);
        $recargoRaw = $request->request->get('recargo_hnl', '');
        $client->setRecargoHnl($recargoRaw !== '' ? number_format(abs((float) $recargoRaw), 2, '.', '') : null);
    }

    #[Route('/vacation/create', name: 'vacation_create', methods: ['POST'])]
    public function createVacation(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('work_vacation', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', $this->translator->trans('work.flash_token_invalid', [], 'work'));
            return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
        }

        $fechaInicio = new \DateTimeImmutable((string) $request->request->get('fecha_inicio'));
        $fechaFin    = new \DateTimeImmutable((string) $request->request->get('fecha_fin'));
        $dias        = max(1, (int) $request->request->get('dias', 1));
        $semestre    = (int) $request->request->get('semestre', 1);
        $notas       = trim((string) $request->request->get('notas', ''));

        $vacation = new WorkVacation();
        $vacation->setFechaInicio($fechaInicio);
        $vacation->setFechaFin($fechaFin);
        $vacation->setDias($dias);
        $vacation->setSemestre($semestre);
        $vacation->setAnio((int) $fechaInicio->format('Y'));
        $vacation->setNotas($notas !== '' ? $notas : null);

        $em->persist($vacation);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('work.flash_vacation_registered', ['%n%' => (string) $dias], 'work'));

        return $this->redirectToRoute('grova_work_index', ['_locale' => $request->getLocale()]);
    }
}
