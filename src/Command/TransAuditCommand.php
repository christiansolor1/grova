<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'grova:trans:audit',
    description: 'Audita traducciones: detecta claves usadas en Twig/PHP/JS que faltan en los YAML',
)]
class TransAuditCommand extends Command
{
    public function __construct(
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('fix', null, InputOption::VALUE_NONE, 'Añade las claves faltantes al YAML con valor vacío');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io  = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');

        $io->title('Grova — Auditoría de traducciones');

        $translationsDir = $this->projectDir . '/translations';
        $yamlEs = $translationsDir . '/messages.es.yaml';
        $yamlEn = $translationsDir . '/messages.en.yaml';

        $existingEs = file_exists($yamlEs) ? Yaml::parseFile($yamlEs) : [];
        $existingEn = file_exists($yamlEn) ? Yaml::parseFile($yamlEn) : [];

        $allKeys = $this->collectAllKeys();

        $missingEs = [];
        $missingEn = [];
        $totalMissing = 0;

        foreach ($allKeys as $file => $keys) {
            $fileMissingEs = [];
            $fileMissingEn = [];

            foreach ($keys as $key) {
                if (!array_key_exists($key, $existingEs)) {
                    $fileMissingEs[] = $key;
                }
                if (!array_key_exists($key, $existingEn)) {
                    $fileMissingEn[] = $key;
                }
            }

            if (!empty($fileMissingEs) || !empty($fileMissingEn)) {
                $shortFile = str_replace($this->projectDir . '/', '', $file);
                $io->section($shortFile);

                $rows = [];
                $allMissing = array_unique(array_merge($fileMissingEs, $fileMissingEn));
                foreach ($allMissing as $key) {
                    $rows[] = [
                        $key,
                        array_key_exists($key, $existingEs) ? '<info>✓</info>' : '<error>✗</error>',
                        array_key_exists($key, $existingEn) ? '<info>✓</info>' : '<error>✗</error>',
                    ];
                    $totalMissing++;
                }

                $io->table(['Clave', 'messages.es', 'messages.en'], $rows);

                foreach ($fileMissingEs as $key) {
                    $missingEs[$key] = '';
                }
                foreach ($fileMissingEn as $key) {
                    $missingEn[$key] = '';
                }
            }
        }

        // Verificar JS sincronizado con YAML
        $this->auditJsSync($io);

        if ($totalMissing === 0) {
            $io->success('Todo está traducido. No faltan claves.');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d clave(s) faltante(s) en total.', $totalMissing));

        if ($fix) {
            if (!empty($missingEs)) {
                $content  = file_get_contents($yamlEs);
                $content .= "\n# ── Añadido por grova:trans:audit --fix ─────────────────────────────────────\n";
                foreach ($missingEs as $key => $val) {
                    $content .= sprintf("'%s': ''\n", addslashes($key));
                }
                file_put_contents($yamlEs, $content);
                $io->success(sprintf('Añadidas %d claves vacías a messages.es.yaml', count($missingEs)));
            }

            if (!empty($missingEn)) {
                $content  = file_get_contents($yamlEn);
                $content .= "\n# ── Añadido por grova:trans:audit --fix ─────────────────────────────────────\n";
                foreach ($missingEn as $key => $val) {
                    $content .= sprintf("'%s': ''\n", addslashes($key));
                }
                file_put_contents($yamlEn, $content);
                $io->success(sprintf('Añadidas %d claves vacías a messages.en.yaml', count($missingEn)));
            }

            $io->note('Rellena los valores vacíos en los YAML y ejecuta grova:trans:build para sincronizar el JS.');
        } else {
            $io->note('Ejecuta con --fix para añadir las claves faltantes automáticamente.');
        }

        return Command::FAILURE;
    }

    // ─── Recolectar claves usadas en Twig, PHP y JS ──────────────────────────

    private function collectAllKeys(): array
    {
        $keys = [];

        // Twig: {{ 'clave' | trans }} y {% block %}{{ 'clave'|trans }}
        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/templates')->name('*.twig');
        foreach ($finder as $file) {
            $found = $this->extractTwigKeys($file->getContents());
            if (!empty($found)) {
                $keys[$file->getRealPath()] = $found;
            }
        }

        // PHP: ->trans('clave') o ->trans("clave")
        $finder = new Finder();
        $finder->files()->in($this->projectDir . '/src')->name('*.php');
        foreach ($finder as $file) {
            $found = $this->extractPhpKeys($file->getContents());
            if (!empty($found)) {
                $keys[$file->getRealPath()] = $found;
            }
        }

        // JS: traducir('clave') o traducir("clave") — excluye los archivos generados
        $jsDir = $this->projectDir . '/public/js';
        if (is_dir($jsDir)) {
            $finder = new Finder();
            $finder->files()->in($jsDir)->name('*.js')->notPath('translations');
            foreach ($finder as $file) {
                $found = $this->extractJsKeys($file->getContents());
                if (!empty($found)) {
                    $keys[$file->getRealPath()] = $found;
                }
            }
        }

        return $keys;
    }

    private function extractTwigKeys(string $content): array
    {
        preg_match_all("/['\"]([^'\"]+)['\"]\s*\|\s*trans/", $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    private function extractPhpKeys(string $content): array
    {
        preg_match_all("/->trans\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    private function extractJsKeys(string $content): array
    {
        preg_match_all("/traducir\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    // ─── Verificar que el JS generado está sincronizado con el YAML ──────────

    private function auditJsSync(SymfonyStyle $io): void
    {
        $translationsDir = $this->projectDir . '/translations';
        $jsDir           = $this->projectDir . '/public/js/translations';

        foreach (['es', 'en'] as $locale) {
            $yamlFile = $translationsDir . '/messages.' . $locale . '.yaml';
            $jsFile   = $jsDir . '/translations.' . $locale . '.js';

            if (!file_exists($jsFile)) {
                $io->warning(sprintf('translations.%s.js no existe — ejecuta grova:trans:build', $locale));
                continue;
            }

            if (!file_exists($yamlFile)) {
                continue;
            }

            $yamlMtime = filemtime($yamlFile);
            $jsMtime   = filemtime($jsFile);

            if ($yamlMtime > $jsMtime) {
                $io->warning(sprintf(
                    'messages.%s.yaml es más reciente que translations.%s.js — ejecuta grova:trans:build',
                    $locale, $locale
                ));
            } else {
                $io->text(sprintf('<info>✓</info> translations.%s.js está sincronizado', $locale));
            }
        }
    }
}
