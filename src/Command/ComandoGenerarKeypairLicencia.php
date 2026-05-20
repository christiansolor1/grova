<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genera el keypair de sodium para el sistema de licencias.
 * Ejecutar UNA sola vez y guardar los valores en .env.local (servidor grova)
 * y LICENSE_PUBLIC_KEY en .env (se puede commitear — es pública).
 *
 * Uso: php bin/console grova:licencias:generar-keypair
 */
#[AsCommand(
    name: 'grova:licencias:generar-keypair',
    description: 'Genera el keypair sodium para el sistema de licencias (ejecutar una sola vez).',
)]
final class ComandoGenerarKeypairLicencia extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->warning('Este comando genera un NUEVO keypair. Si ya tienes licencias emitidas, las claves anteriores dejarán de funcionar.');

        if (!$io->confirm('¿Continuar?', false)) {
            $io->note('Operación cancelada.');

            return Command::SUCCESS;
        }

        $keypair = sodium_crypto_sign_keypair();
        $clavePrivada = sodium_crypto_sign_secretkey($keypair);
        $clavePublica = sodium_crypto_sign_publickey($keypair);

        $keypairB64 = base64_encode($keypair);
        $publicaB64 = base64_encode($clavePublica);

        $io->success('Keypair generado correctamente.');

        $io->section('Agrega esto a tu .env.local en el servidor de grova (PRIVADO — nunca commitear):');
        $io->writeln("LICENSE_KEYPAIR={$keypairB64}");

        $io->section('Agrega esto a .env del proyecto (puede ir en git — es pública):');
        $io->writeln("LICENSE_PUBLIC_KEY={$publicaB64}");

        $io->note([
            'La clave privada (LICENSE_KEYPAIR) solo debe estar en TU servidor.',
            'La clave pública (LICENSE_PUBLIC_KEY) se commitea al repo y valida licencias en instalaciones on-premise.',
            'Si pierdes la clave privada no podrás generar nuevas licencias — guárdala en un lugar seguro.',
        ]);

        sodium_memzero($clavePrivada);
        sodium_memzero($keypair);

        return Command::SUCCESS;
    }
}
