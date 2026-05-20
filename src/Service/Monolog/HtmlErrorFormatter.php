<?php

declare(strict_types=1);

namespace App\Service\Monolog;

use Monolog\Formatter\HtmlFormatter;
use Monolog\LogRecord;

/**
 * Formatea errores como HTML para correos electrónicos.
 *
 * Extiende Monolog\Formatter\HtmlFormatter para que el MailerHandler
 * de Symfony detecte instanceof HtmlFormatter y llame a $message->html().
 */
final class HtmlErrorFormatter extends HtmlFormatter
{
    public function format(LogRecord $record): string
    {
        $channel  = $record->channel;
        $level    = $record->level->getName();
        $message  = $record->message;
        $context  = $record->context;
        $datetime = $record->datetime;

        $exception = $context['exception'] ?? null;
        $extra     = $record->extra ?? [];

        // Raw log line for copy-paste
        $rawLine = $this->formatearLineaCruda($record);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error en Grova</title>';
        $html .= '<style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
            .container { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
            .header { background: #dc2626; color: #fff; padding: 16px 24px; }
            .header h1 { margin: 0; font-size: 18px; font-weight: 600; }
            .header .level { font-size: 12px; opacity: .8; margin-top: 2px; }
            .body { padding: 24px; }
            .field { margin-bottom: 16px; }
            .field-label { font-size: 11px; text-transform: uppercase; color: #6b7280; font-weight: 600; letter-spacing: .5px; margin-bottom: 4px; }
            .field-value { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; font-family: "SFMono-Regular", Consolas, monospace; font-size: 13px; white-space: pre-wrap; word-break: break-all; }
            .field-value.file { color: #6b7280; font-size: 12px; }

            .raw-line { background: #1e293b; color: #e2e8f0; border: 1px solid #334155; border-radius: 6px; padding: 14px; font-family: "SFMono-Regular", Consolas, monospace; font-size: 12px; line-height: 1.5; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto; -webkit-user-select: all; user-select: all; }
        </style></head><body>';
        $html .= '<div class="container">';
        $html .= '<div class="header"><h1>🚨 ' . htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') . '</h1><div class="level">' . $level . ' · ' . $channel . '</div></div>';
        $html .= '<div class="body">';

        // Raw log line (user-select: all para copiar fácil)
        $html .= '<div class="field"><div class="field-label">Línea completa</div>';
        $html .= '<div class="raw-line">' . htmlspecialchars($rawLine, ENT_QUOTES, 'UTF-8') . '</div></div>';

        // Timestamp
        $html .= '<div class="field"><div class="field-label">Timestamp</div><div class="field-value">' . $datetime->format('Y-m-d H:i:s T') . '</div></div>';

        if ($exception instanceof \Throwable) {
            $html .= '<div class="field"><div class="field-label">Tipo</div><div class="field-value">' . htmlspecialchars($exception::class, ENT_QUOTES, 'UTF-8') . '</div></div>';
            $html .= '<div class="field"><div class="field-label">Archivo</div><div class="field-value file">' . htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8') . ':' . $exception->getLine() . '</div></div>';

            $html .= '<div class="field"><div class="field-label">Trace</div><div class="field-value" style="font-size:11px;">' . htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</div></div>';
        } elseif (is_array($exception)) {
            $html .= '<div class="field"><div class="field-label">Tipo</div><div class="field-value">' . htmlspecialchars($exception['class'] ?? 'Exception', ENT_QUOTES, 'UTF-8') . '</div></div>';
            $html .= '<div class="field"><div class="field-label">Archivo</div><div class="field-value file">' . htmlspecialchars($exception['file'] ?? '', ENT_QUOTES, 'UTF-8') . ':' . ($exception['line'] ?? '') . '</div></div>';

            $trace = $exception['trace'] ?? '';
            if ($trace) {
                $html .= '<div class="field"><div class="field-label">Trace</div><div class="field-value" style="font-size:11px;">' . htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') . '</div></div>';
            }
        }

        if (!empty($extra)) {
            $html .= '<div class="field"><div class="field-label">Extra</div><div class="field-value">' . htmlspecialchars(json_encode($extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') . '</div></div>';
        }

        $html .= '</div></div></body></html>';

        return $html;
    }

    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }

        return $output;
    }

    /**
     * Construye la línea cruda como aparece en el log de consola.
     */
    private function formatearLineaCruda(LogRecord $record): string
    {
        $datetime = $record->datetime;
        $channel  = $record->channel;
        $level    = $record->level->getName();
        $message  = $record->message;
        $context  = $record->context;
        $extra    = $record->extra;

        $timestamp = $datetime->format('Y-m-d\TH:i:s.uP');

        $contextJson = !empty($context) ? ' ' . $this->jsonEncodeContext($context) : ' {}';
        $extraJson   = !empty($extra) ? ' ' . json_encode($extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ' []';

        return sprintf(
            '[%s] %s.%s: %s%s%s',
            $timestamp,
            $channel,
            $level,
            $message,
            $contextJson,
            $extraJson,
        );
    }

    /**
     * Convierte el context a JSON, formateando excepciones como lo hace Monolog.
     */
    private function jsonEncodeContext(array $context): string
    {
        $normalized = [];
        foreach ($context as $key => $value) {
            if ($value instanceof \Throwable) {
                $normalized[$key] = $this->formatearExcepcion($value);
            } elseif (\is_array($value)) {
                $normalized[$key] = $this->normalizarArray($value);
            } else {
                $normalized[$key] = $value;
            }
        }

        return json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Normaliza un array, formateando excepciones anidadas.
     */
    private function normalizarArray(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value instanceof \Throwable) {
                $result[$key] = $this->formatearExcepcion($value);
            } elseif (\is_array($value)) {
                $result[$key] = $this->normalizarArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Formatea una excepción como Monolog: [object] (ExceptionClass(code: X): message at file:line)
     */
    private function formatearExcepcion(\Throwable $e): string
    {
        $file = str_replace(\DIRECTORY_SEPARATOR, '/', $e->getFile());

        return sprintf(
            '[object] (%s(code: %d): %s at %s:%d)',
            $e::class,
            $e->getCode(),
            $e->getMessage(),
            $file,
            $e->getLine(),
        );
    }
}
