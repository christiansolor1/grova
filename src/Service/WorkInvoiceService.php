<?php

declare(strict_types=1);

namespace App\Service;

use App\Module\Personal\Work\Entity\WorkClient;
use App\Module\Personal\Work\Entity\WorkDay;
use App\Module\Personal\Work\Entity\WorkInvoice;
use App\Module\Personal\Work\Entity\WorkInvoiceBonusLine;
use App\Module\Personal\Work\WorkMonthLabel;
use App\Module\Personal\Work\Repository\WorkDayRepository;
use App\Module\Personal\Work\Repository\WorkInvoiceRepository;
use App\Service\ExchangeRateService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

final class WorkInvoiceService
{
    public function __construct(
        private readonly WorkInvoiceRepository $invoiceRepo,
        private readonly WorkDayRepository $dayRepo,
        private readonly EntityManagerInterface $em,
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
        private readonly RequestStack $requestStack,
        private readonly ExchangeRateService $exchangeRate,
    ) {
    }

    private function invoiceMonthLabel(WorkInvoice $invoice): string
    {
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es';

        return WorkMonthLabel::format($invoice->getAnio(), $invoice->getMes(), $locale);
    }

    /**
     * Genera (o actualiza) la factura de un mes calculando días trabajados y bonus.
     */
    public function generateForMonth(WorkClient $client, int $year, int $month): WorkInvoice
    {
        $days = $this->dayRepo->findByMonth($year, $month);

        $diasTrabajados = 0;
        $diasBonus      = 0;

        foreach ($days as $day) {
            if ($day->trabajado()) {
                $diasTrabajados++;
                if ($day->bonusAplica()) {
                    $diasBonus++;
                }
            }
        }

        $salarioBase = $client->getSalarioBaseFloat();
        $bonusDia    = $client->getBonusDiaFloat();
        $montoBonus  = round($diasBonus * $bonusDia, 2);

        $existing = $this->invoiceRepo->findForMonth($client, $year, $month);
        $isNew    = $existing === null;
        $invoice  = $existing ?? new WorkInvoice();

        $invoice->setClient($client);
        $invoice->setAnio($year);
        $invoice->setMes($month);
        $invoice->setDiasTrabajados($diasTrabajados);
        $invoice->setDiasBonus($diasBonus);
        $invoice->setSalarioBase(number_format($salarioBase, 2, '.', ''));
        $invoice->setMontoBonus(number_format($montoBonus, 2, '.', ''));
        $this->recalculateInvoiceTotal($invoice);

        if ($isNew) {
            $invoice->setNumero($this->invoiceRepo->findNextNumero());
            $invoice->setEstado('borrador');
        }

        $this->applyEmissionFxSnapshot($invoice);

        $this->em->persist($invoice);
        $this->em->flush();

        return $invoice;
    }

    /**
     * Genera el PDF de la factura y devuelve los bytes.
     */
    public function generatePdf(WorkInvoice $invoice): string
    {
        $days = $this->dayRepo->findByMonth($invoice->getAnio(), $invoice->getMes());

        $html = $this->twig->render('work/invoice_pdf.html.twig', [
            'invoice'              => $invoice,
            'client'               => $invoice->getClient(),
            'days'                 => $days,
            'invoice_month_label'  => $this->invoiceMonthLabel($invoice),
            'pdf_locale'           => $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es',
        ]);

        return $this->renderPdf($html);
    }

    /**
     * Genera el PDF externo (para enviar al cliente) con formato de recibo.
     */
    public function generatePdfExternal(WorkInvoice $invoice): string
    {
        $html = $this->twig->render('work/invoice_pdf_external.html.twig', [
            'invoice'             => $invoice,
            'client'              => $invoice->getClient(),
            'invoice_month_label' => $this->invoiceMonthLabel($invoice),
            'pdf_locale'          => $this->requestStack->getCurrentRequest()?->getLocale() ?? 'es',
        ]);

        return $this->renderPdf($html);
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function applyEmissionFxSnapshot(WorkInvoice $invoice): void
    {
        $r = $this->exchangeRate->getRates();
        $eur = (float) ($r['eur'] ?? 0);
        if ($eur <= 0) {
            return;
        }
        $usd = (float) ($r['usd'] ?? 0);
        $invoice->setTasaEmisionEurL(number_format($eur, 5, '.', ''));
        $invoice->setTasaEmisionUsdL($usd > 0 ? number_format($usd, 5, '.', '') : null);
        $fecha = isset($r['fecha']) ? (string) $r['fecha'] : '';
        $invoice->setTasaEmisionFecha($fecha !== '' ? mb_substr($fecha, 0, 16) : null);
        $src = isset($r['source']) ? (string) $r['source'] : '';
        $invoice->setTasaEmisionSource($src !== '' ? mb_substr($src, 0, 64) : null);
    }

    /**
     * Marca la factura como enviada y manda el PDF por email al cliente.
     */
    public function sendByEmail(WorkInvoice $invoice, string $fromEmail, string $fromName = 'GROVA Work'): void
    {
        $pdf         = $this->generatePdf($invoice);
        $client      = $invoice->getClient();
        $mesLabel    = $this->invoiceMonthLabel($invoice);
        $filename    = sprintf('Factura_%s_%s.pdf', $client->getNombre(), date('Y_m', mktime(0, 0, 0, $invoice->getMes(), 1, $invoice->getAnio())));

        $email = (new TemplatedEmail())
            ->from(sprintf('%s <%s>', $fromName, $fromEmail))
            ->subject(sprintf('Factura %s — %s', $mesLabel, $client->getNombre()))
            ->htmlTemplate('work/invoice_email.html.twig')
            ->context([
                'invoice' => $invoice,
                'client'  => $client,
                'mes'     => $mesLabel,
            ])
            ->addPart(new DataPart($pdf, $filename, 'application/pdf'));

        foreach ($client->getEmailsFacturaArray() as $to) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $email->addTo($to);
            }
        }

        $this->mailer->send($email);

        $invoice->setEstado('enviada');
        $invoice->setEnviadaAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    /**
     * Sustituye las líneas de bono extra y recalcula el total.
     *
     * @param list<array{eur: float, concepto: string}> $lines Solo se guardan filas con importe &gt; 0 (máx. 40).
     */
    public function replaceExtraBonusLines(WorkInvoice $invoice, array $lines): void
    {
        foreach ($invoice->getBonusLines()->toArray() as $line) {
            $invoice->removeBonusLine($line);
        }

        $order = 0;
        foreach (\array_slice($lines, 0, 40) as $row) {
            $eur = max(0.0, round((float) ($row['eur'] ?? 0), 2));
            if ($eur <= 0.0) {
                continue;
            }
            $raw = (string) ($row['concepto'] ?? '');
            $raw = trim($raw);
            $concepto = $raw === '' ? null : mb_substr($raw, 0, 255);

            $line = new WorkInvoiceBonusLine();
            $line->setSortOrder($order++);
            $line->setImporteEur(number_format($eur, 2, '.', ''));
            $line->setConcepto($concepto);
            $invoice->addBonusLine($line);
        }

        $this->recalculateInvoiceTotal($invoice);
        $this->em->flush();
    }

    private function recalculateInvoiceTotal(WorkInvoice $invoice): void
    {
        $total = round(
            $invoice->getSalarioBaseFloat()
            + $invoice->getMontoBonusFloat()
            + $invoice->getExtraBonusSumFloat(),
            2,
        );
        $invoice->setTotal(number_format($total, 2, '.', ''));
    }
}
