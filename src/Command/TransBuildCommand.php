<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'grova:trans:build',
    description: 'Genera los archivos JS de traducciones desde los YAML (fuente de verdad)',
)]
class TransBuildCommand extends Command
{
    public function __construct(
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Grova — Generador de traducciones JS');

        $translationsDir = $this->projectDir . '/translations';
        $outputDir       = $this->projectDir . '/public/js/translations';

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Detectar todos los locales disponibles en cualquier YAML de traducción
        $locales = [];
        foreach (glob($translationsDir . '/*.*.yaml') as $file) {
            preg_match('/\.(\w{2})\.yaml$/', $file, $m);
            if (!empty($m[1])) {
                $locales[$m[1]] = true;
            }
        }
        $locales = array_keys($locales);

        if (empty($locales)) {
            $io->error('No se encontraron archivos de traducción en ' . $translationsDir);
            return Command::FAILURE;
        }

        foreach ($locales as $locale) {
            $jsFile = $outputDir . '/translations.' . $locale . '.js';

            // Fusionar todos los dominios del locale en un único array
            $merged = [];
            $domains = [];
            foreach (glob($translationsDir . '/*.' . $locale . '.yaml') as $yamlFile) {
                $domain = basename($yamlFile, '.' . $locale . '.yaml');
                $data   = Yaml::parseFile($yamlFile) ?? [];
                $merged = array_merge($merged, $data);
                $domains[] = $domain;
            }

            $js = $this->generateJs($locale, $merged, $domains);
            file_put_contents($jsFile, $js);

            $io->success(sprintf(
                '[%s] → %s (%d claves, dominios: %s)',
                $locale,
                str_replace($this->projectDir . '/', '', $jsFile),
                count($merged),
                implode(', ', $domains)
            ));
        }

        $io->note('Incluye el archivo en base.html.twig:');
        $io->text('<script src="{{ asset(\'js/translations/translations.\' ~ app.request.locale ~ \'.js\') }}"></script>');

        return Command::SUCCESS;
    }

    private function generateJs(string $locale, array $translations, array $domains = []): string
    {
        $cases = '';
        foreach ($translations as $key => $value) {
            $safeKey   = addslashes((string) $key);
            $safeValue = addslashes((string) $value);
            $cases    .= sprintf("        case '%s': return '%s';\n", $safeKey, $safeValue);
        }

        $domainList = implode(', ', $domains);
        return <<<JS
        // AUTO-GENERADO por grova:trans:build — no editar manualmente
        // Fuente de verdad: translations/*.{$locale}.yaml  (dominios: {$domainList})
        // Para actualizar: php bin/console grova:trans:build

        var _grovaLocale = '{$locale}';

        function traducir(texto) {
            switch (texto) {
        {$cases}
                default: return texto;
            }
        }
        JS;
    }
}
