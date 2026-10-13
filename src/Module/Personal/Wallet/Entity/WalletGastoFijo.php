<?php

declare(strict_types=1);

namespace App\Module\Personal\Wallet\Entity;

use App\Module\Personal\Wallet\Repository\WalletGastoFijoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletGastoFijoRepository::class)]
#[ORM\Table(name: 'wallet_gasto_fijo')]
class WalletGastoFijo
{
    /**
     * generico      → gasto fijo simple (agua, luz, gym)
     * financiado    → compra financiada con prima y cuotas (iPhone, Jestereo, etc.)
     * prestamo      → préstamo bancario o personal
     * suscripcion   → suscripción digital (Netflix, Spotify, etc.)
     */
    public const TIPOS = ['generico', 'financiado', 'prestamo', 'suscripcion'];

    public const TIPO_LABELS = [
        'generico'   => 'Servicio / gasto simple',
        'financiado' => 'Compra financiada',
        'prestamo'   => 'Préstamo',
        'suscripcion'=> 'Suscripción digital',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $tenantId;

    #[ORM\Column(length: 100)]
    private string $nombre;

    /** Monto base mensual. Para financiados: monto que queda al terminar el financiamiento. */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $monto = '0.00';

    /** Día del mes en que se paga (1-31) */
    #[ORM\Column]
    private int $diaPago = 1;

    /** Día del mes en que se corta el servicio si no se pagó (nullable — solo servicios con corte) */
    #[ORM\Column(nullable: true)]
    private ?int $diaCorte = null;

    /** mensual | quincenal | semanal | irregular */
    #[ORM\Column(length: 20)]
    private string $frecuencia = 'mensual';

    /** generico | financiado | prestamo | suscripcion */
    #[ORM\Column(length: 20)]
    private string $tipo = 'generico';

    // ── Campos de financiamiento (tipo = financiado | prestamo) ──────────────

    /** Fecha en que se firmó/inició el contrato */
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaInicioPlan = null;

    /** Total de meses del financiamiento (ej: 18) */
    #[ORM\Column(nullable: true)]
    private ?int $mesesFinanciamiento = null;

    /** Prima / enganche pagado al inicio */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montoPrima = null;

    /**
     * Cuotas con montos especiales en JSON.
     * Formato: [{"desde":1,"hasta":3,"monto":3431.00},{"desde":4,"hasta":18,"monto":3760.00}]
     * Si está vacío, todas las cuotas usan `monto`.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cuotasEspeciales = null;

    /**
     * Monto que queda al terminar el financiamiento (solo para tipo financiado con servicio continuo).
     * Ej: el plan de consumo de L 1,600 que queda después de pagar el teléfono.
     * null = el gasto termina al acabar el financiamiento.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montoPostFinanciamiento = null;

    // ── Campos de tipo generico (agua, botellón, etc.) ────────────────────────

    /** Unidad de medida: botellón, galón, libra, caja, etc. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unidad = null;

    /** Presentación: 20L, 500ml, 25lbs, 12 unidades, etc. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $presentacion = null;

    /** Precio por unidad (opcional) */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $precioUnitario = null;

    /** Cantidad base planificada por período (ej: 7 botellones) */
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $cantidadBase = null;

    // ── Relaciones ────────────────────────────────────────────────────────────

    #[ORM\ManyToOne(targetEntity: WalletCuenta::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletCuenta $cuenta = null;

    #[ORM\ManyToOne(targetEntity: WalletCategory::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WalletCategory $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nota = null;

    /** URL directa para pagar (portal del proveedor, app, etc.) */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $urlPago = null;

    /** Número de cliente / cuenta en la empresa proveedora (Claro, Tigo, etc.) */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroCliente = null;

    /** Usuario o correo de acceso al servicio */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $usuarioAcceso = null;

    /** Contraseña / credencial — cifrada con AES-256 */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $claveAcceso = null;

    #[ORM\Column]
    private bool $activo = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters / Setters básicos ─────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getTenantId(): int { return $this->tenantId; }
    public function setTenantId(int $tenantId): static { $this->tenantId = $tenantId; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getMonto(): string { return $this->monto; }
    public function getMontoFloat(): float { return (float) $this->monto; }
    public function setMonto(string $monto): static { $this->monto = $monto; return $this; }

    public function getDiaPago(): int { return $this->diaPago; }
    public function setDiaPago(int $diaPago): static { $this->diaPago = $diaPago; return $this; }

    public function getDiaCorte(): ?int { return $this->diaCorte; }
    public function setDiaCorte(?int $diaCorte): static { $this->diaCorte = $diaCorte; return $this; }

    public function getFrecuencia(): string { return $this->frecuencia; }
    public function setFrecuencia(string $frecuencia): static { $this->frecuencia = $frecuencia; return $this; }

    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $tipo): static { $this->tipo = $tipo; return $this; }
    public function getTipoLabel(): string { return self::TIPO_LABELS[$this->tipo] ?? $this->tipo; }

    public function getFechaInicioPlan(): ?\DateTimeImmutable { return $this->fechaInicioPlan; }
    public function setFechaInicioPlan(?\DateTimeImmutable $v): static { $this->fechaInicioPlan = $v; return $this; }

    public function getMesesFinanciamiento(): ?int { return $this->mesesFinanciamiento; }
    public function setMesesFinanciamiento(?int $v): static { $this->mesesFinanciamiento = $v; return $this; }

    public function getMontoPrima(): ?string { return $this->montoPrima; }
    public function getMontoPrimaFloat(): float { return (float) ($this->montoPrima ?? 0); }
    public function setMontoPrima(?string $v): static { $this->montoPrima = $v; return $this; }

    public function getCuotasEspeciales(): ?string { return $this->cuotasEspeciales; }
    public function getCuotasEspecialesArray(): array
    {
        if ($this->cuotasEspeciales === null || $this->cuotasEspeciales === '') {
            return [];
        }
        return json_decode($this->cuotasEspeciales, true) ?? [];
    }
    public function setCuotasEspeciales(?string $v): static { $this->cuotasEspeciales = $v; return $this; }

    public function getMontoPostFinanciamiento(): ?string { return $this->montoPostFinanciamiento; }
    public function getMontoPostFinanciamientoFloat(): float { return (float) ($this->montoPostFinanciamiento ?? 0); }
    public function setMontoPostFinanciamiento(?string $v): static { $this->montoPostFinanciamiento = $v; return $this; }

    public function getUnidad(): ?string { return $this->unidad; }
    public function setUnidad(?string $unidad): static { $this->unidad = $unidad; return $this; }

    public function getPresentacion(): ?string { return $this->presentacion; }
    public function setPresentacion(?string $presentacion): static { $this->presentacion = $presentacion; return $this; }

    public function getPrecioUnitario(): ?string { return $this->precioUnitario; }
    public function getPrecioUnitarioFloat(): float { return (float) ($this->precioUnitario ?? 0); }
    public function setPrecioUnitario(?string $precioUnitario): static { $this->precioUnitario = $precioUnitario; return $this; }

    public function getCantidadBase(): ?string { return $this->cantidadBase; }
    public function getCantidadBaseFloat(): float { return (float) ($this->cantidadBase ?? 0); }
    public function setCantidadBase(?string $cantidadBase): static { $this->cantidadBase = $cantidadBase; return $this; }

    public function getCuenta(): ?WalletCuenta { return $this->cuenta; }
    public function setCuenta(?WalletCuenta $cuenta): static { $this->cuenta = $cuenta; return $this; }

    public function getCategory(): ?WalletCategory { return $this->category; }
    public function setCategory(?WalletCategory $category): static { $this->category = $category; return $this; }

    public function getNota(): ?string { return $this->nota; }
    public function setNota(?string $nota): static { $this->nota = $nota; return $this; }

    public function getUrlPago(): ?string { return $this->urlPago; }
    public function setUrlPago(?string $urlPago): static { $this->urlPago = $urlPago; return $this; }

    public function getNumeroCliente(): ?string { return $this->numeroCliente; }
    public function setNumeroCliente(?string $numeroCliente): static { $this->numeroCliente = $numeroCliente; return $this; }

    public function getUsuarioAcceso(): ?string { return $this->usuarioAcceso; }
    public function setUsuarioAcceso(?string $usuarioAcceso): static { $this->usuarioAcceso = $usuarioAcceso; return $this; }

    public function getClaveAcceso(): ?string { return $this->claveAcceso; }
    public function setClaveAcceso(?string $claveAcceso): static { $this->claveAcceso = $claveAcceso; return $this; }

    public function isActivo(): bool { return $this->activo; }
    public function setActivo(bool $activo): static { $this->activo = $activo; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    // ── Lógica de financiamiento ──────────────────────────────────────────────

    /** Número de cuota para un mes/año dado (1-based). null si no tiene financiamiento. */
    public function getCuotaNumero(int $anio, int $mes): ?int
    {
        if ($this->fechaInicioPlan === null || $this->mesesFinanciamiento === null) {
            return null;
        }

        $inicioAnio = (int) $this->fechaInicioPlan->format('Y');
        $inicioMes  = (int) $this->fechaInicioPlan->format('n');

        $num = ($anio - $inicioAnio) * 12 + ($mes - $inicioMes) + 1;

        return $num >= 1 ? $num : null;
    }

    /** ¿Está el financiamiento activo en ese mes/año? */
    public function isFinanciamientoActivo(int $anio, int $mes): bool
    {
        $num = $this->getCuotaNumero($anio, $mes);
        if ($num === null || $this->mesesFinanciamiento === null) {
            return false;
        }
        return $num >= 1 && $num <= $this->mesesFinanciamiento;
    }

    /** Monto real a pagar en ese mes/año, considerando cuotas especiales y fase post-financiamiento. */
    public function getMontoParaMes(int $anio, int $mes): float
    {
        $cuotaNum = $this->getCuotaNumero($anio, $mes);

        // Sin financiamiento o antes del inicio → monto base
        if ($cuotaNum === null) {
            return $this->getMontoFloat();
        }

        // Financiamiento terminado
        if ($this->mesesFinanciamiento !== null && $cuotaNum > $this->mesesFinanciamiento) {
            return $this->getMontoPostFinanciamientoFloat() ?: $this->getMontoFloat();
        }

        // Buscar en cuotas especiales
        foreach ($this->getCuotasEspecialesArray() as $tramo) {
            $desde = (int) ($tramo['desde'] ?? 0);
            $hasta = (int) ($tramo['hasta'] ?? 0);
            if ($cuotaNum >= $desde && $cuotaNum <= $hasta) {
                return (float) ($tramo['monto'] ?? 0);
            }
        }

        return $this->getMontoFloat();
    }

    /** Fecha estimada en que termina el financiamiento. */
    public function getFechaFinPlan(): ?\DateTimeImmutable
    {
        if ($this->fechaInicioPlan === null || $this->mesesFinanciamiento === null) {
            return null;
        }
        return $this->fechaInicioPlan->modify('+' . ($this->mesesFinanciamiento - 1) . ' months');
    }

    /** Meses restantes de financiamiento desde hoy. */
    public function getMesesRestantes(): ?int
    {
        $fin = $this->getFechaFinPlan();
        if ($fin === null) {
            return null;
        }

        $hoy  = new \DateTimeImmutable();
        $anio = (int) $hoy->format('Y');
        $mes  = (int) $hoy->format('n');

        $cuotaActual = $this->getCuotaNumero($anio, $mes);
        if ($cuotaActual === null || $this->mesesFinanciamiento === null) {
            return null;
        }

        return max(0, $this->mesesFinanciamiento - $cuotaActual + 1);
    }

    /** Costo total financiado (prima + suma de todas las cuotas del dispositivo). */
    public function getCostoTotalFinanciado(): float
    {
        if ($this->fechaInicioPlan === null || $this->mesesFinanciamiento === null) {
            return 0.0;
        }

        $total    = $this->getMontoPrimaFloat();
        $inicioA  = (int) $this->fechaInicioPlan->format('Y');
        $inicioM  = (int) $this->fechaInicioPlan->format('n');

        for ($i = 0; $i < $this->mesesFinanciamiento; $i++) {
            $mes  = (($inicioM - 1 + $i) % 12) + 1;
            $anio = $inicioA + (int) floor(($inicioM - 1 + $i) / 12);
            $total += $this->getMontoParaMes($anio, $mes);
        }

        // Restar el monto post-financiamiento por cada cuota (es el componente de servicio)
        $montoServicio = $this->getMontoPostFinanciamientoFloat() ?: $this->getMontoFloat();
        $total -= $montoServicio * $this->mesesFinanciamiento;

        return max(0.0, $total);
    }

    /** Determina el estado del gasto para un mes/año dado */
    /** Último día real de un mes (para clampear día 31 en meses cortos). */
    private function ultimoDiaMes(int $anio, int $mes): int
    {
        return (int) (new \DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes)))->format('t');
    }

    /** Día de pago efectivo en un mes dado (clampea al último día si el mes es corto). */
    public function getDiaPagoEfectivo(int $anio, int $mes): int
    {
        return min($this->diaPago, $this->ultimoDiaMes($anio, $mes));
    }

    /** Día de corte efectivo en un mes dado (null si no aplica). */
    public function getDiaCorteEfectivo(int $anio, int $mes): ?int
    {
        if ($this->diaCorte === null) {
            return null;
        }
        return min($this->diaCorte, $this->ultimoDiaMes($anio, $mes));
    }

    public function getEstado(int $anio, int $mes, ?WalletGastoFijoPago $pago): string
    {
        if ($pago !== null) {
            return 'pagado';
        }

        $hoy     = new \DateTimeImmutable();
        $hoyAnio = (int) $hoy->format('Y');
        $hoyMes  = (int) $hoy->format('n');
        $hoyDia  = (int) $hoy->format('j');

        if ($anio < $hoyAnio || ($anio === $hoyAnio && $mes < $hoyMes)) {
            return 'vencido';
        }

        if ($anio === $hoyAnio && $mes === $hoyMes) {
            $diaPagoEfectivo = $this->getDiaPagoEfectivo($anio, $mes);
            if ($hoyDia > $diaPagoEfectivo) {
                // Si tiene día de corte y aún no pasó, es "en riesgo" pero no vencido
                $diaCorteEfectivo = $this->getDiaCorteEfectivo($anio, $mes);
                if ($diaCorteEfectivo !== null && $hoyDia < $diaCorteEfectivo) {
                    return 'en_riesgo';
                }
                return 'vencido';
            }
        }

        return 'pendiente';
    }
}
