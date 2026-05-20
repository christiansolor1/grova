<?php

declare(strict_types=1);

namespace App\Service\Respaldo;

/**
 * Interfaz genérica para exportar/importar datos de un módulo.
 *
 * Cada módulo implementa esta interfaz para definir qué tablas y archivos
 * incluye en el respaldo.
 */
interface RespaldoModuloInterface
{
    /**
     * Nombre clave del módulo (ej: "work", "wallet").
     */
    public function nombreModulo(): string;

    /**
     * Etiqueta legible (ej: "Work — Días trabajados").
     */
    public function etiqueta(): string;

    /**
     * Exporta todos los datos del módulo.
     *
     * @return array{data: array<string, list<array<string, mixed>>>, archivos: list<array{nombre: string, original: string, mime: string}>}
     */
    public function exportar(): array;

    /**
     * Importa datos previamente exportados.
     *
     * @param array<string, list<array<string, mixed>>> $data    Datos por tabla
     * @param string                                    $dir     Directorio temporal con archivos extraídos
     */
    public function importar(array $data, string $dir): void;
}
