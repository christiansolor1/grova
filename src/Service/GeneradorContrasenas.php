<?php

declare(strict_types=1);

namespace App\Service;

final class GeneradorContrasenas
{
    /** Palabras comunes en español para frases memorables */
    private const PALABRAS = [
        'casa', 'perro', 'gato', 'soles', 'luna', 'mar', 'piedra', 'verde',
        'alto', 'bajo', 'trota', 'vuela', 'flor', 'monte', 'vino', 'pan',
        'cielo', 'hoja', 'fruta', 'nieve', 'fuego', 'agua', 'ritmo', 'vida',
        'nube', 'campo', 'selva', 'playa', 'lago', 'viento', 'niebla', 'bosque',
        'tarde', 'noche', 'punto', 'bravo', 'senda', 'roble', 'olivo', 'sauce',
        'veloz', 'firme', 'claro', 'sutil', 'breve', 'fuerte', 'dulce', 'agil',
        'rojo', 'azul', 'rubio', 'negro', 'blanco', 'gruta', 'valle', 'duna',
        'tren', 'barco', 'ala', 'sol', 'pez', 'red', 'sal', 'col', 'pan', 'piel',
    ];

    /**
     * Genera una frase memorable tipo: "perro-verde-trota-42"
     * Fácil de recordar, difícil de hackear.
     */
    public function generarFraseMemorable(int $numPalabras = 5): string
    {
        $palabras = [];

        // Evitar repetir palabras
        $indices = (array) array_rand(self::PALABRAS, min($numPalabras, count(self::PALABRAS)));
        shuffle($indices);

        foreach ($indices as $i) {
            $palabras[] = self::PALABRAS[$i];
        }

        $numero = random_int(10, 99);

        return implode('-', $palabras) . '-' . $numero;
    }

    /**
     * Genera una clave fuerte aleatoria (para gestores de contraseñas)
     */
    public function generarClaveFuerte(int $longitud = 24): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $max   = strlen($chars) - 1;
        $clave = '';

        for ($i = 0; $i < $longitud; $i++) {
            $clave .= $chars[random_int(0, $max)];
        }

        return $clave;
    }

    /**
     * Evalúa fortaleza de una contraseña (0-100)
     * Basado en: longitud, variedad de caracteres, patrones comunes
     */
    public static function evaluarFortaleza(string $contrasena): int
    {
        $puntos = 0;

        if (strlen($contrasena) === 0) {
            return 0;
        }

        // Largo: +0 a +40
        $len = strlen($contrasena);
        $puntos += min(40, (int) ($len * 2.5));

        // Variedad de tipos de caracteres
        $tieneMinuscula = preg_match('/[a-z]/', $contrasena) ? 1 : 0;
        $tieneMayuscula = preg_match('/[A-Z]/', $contrasena) ? 1 : 0;
        $tieneNumero    = preg_match('/[0-9]/', $contrasena) ? 1 : 0;
        $tieneSimbolo   = preg_match('/[^a-zA-Z0-9]/', $contrasena) ? 1 : 0;

        $tipos = $tieneMinuscula + $tieneMayuscula + $tieneNumero + $tieneSimbolo;

        // +5 por cada tipo de carácter presente
        $puntos += $tipos * 5;

        // Bonus por mezclar tipos (+10 si tiene 3+ tipos)
        if ($tipos >= 3) {
            $puntos += 10;
        }

        // Penalizar si tiene caracteres repetidos
        $repeticiones = 0;
        for ($i = 0; $i < $len - 2; $i++) {
            if ($contrasena[$i] === $contrasena[$i + 1] && $contrasena[$i] === $contrasena[$i + 2]) {
                $repeticiones++;
            }
        }
        $puntos -= $repeticiones * 5;

        // Secuencias comunes (123, abc, qwerty) — penalización simple
        $secuencias = ['123', 'abc', 'qwerty', 'asdf', 'zxcv', '000', '111', 'password', 'contrasena', 'admin'];
        foreach ($secuencias as $seq) {
            if (mb_strpos(mb_strtolower($contrasena), $seq) !== false) {
                $puntos -= 20;
            }
        }

        return max(0, min(100, $puntos));
    }

    /**
     * Devuelve nivel de fortaleza textual y color
     */
    public static function nivelFortaleza(int $score): array
    {
        return match (true) {
            $score >= 80 => ['label' => 'Excelente', 'color' => '#22c55e', 'nivel' => 4],
            $score >= 60 => ['label' => 'Buena',    'color' => '#84cc16', 'nivel' => 3],
            $score >= 40 => ['label' => 'Regular',  'color' => '#eab308', 'nivel' => 2],
            $score >= 20 => ['label' => 'Débil',    'color' => '#f97316', 'nivel' => 1],
            default      => ['label' => 'Muy débil', 'color' => '#ef4444', 'nivel' => 0],
        };
    }
}
