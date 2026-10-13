<?php

declare(strict_types=1);

namespace App\Command;

use App\Module\Personal\Work\Entity\WorkClient;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:encriptar-datos-bancarios',
    description: 'Encripta los datos bancarios existentes en texto plano (banc_swift, banc_cuenta en work_client).',
)]
final class ComandoEncriptarDatosBancarios extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EncryptionService $encryption,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $clients = $this->em->getRepository(WorkClient::class)->findAll();
        $count   = 0;

        foreach ($clients as $client) {
            $changed = false;

            // banc_swift
            $swift = $client->getBancSwift();
            if ($swift !== null && !$this->isEncrypted($swift)) {
                $client->setBancSwift($this->encryption->encrypt($swift));
                $changed = true;
            }

            // banc_cuenta
            $cuenta = $client->getBancCuenta();
            if ($cuenta !== null && !$this->isEncrypted($cuenta)) {
                $client->setBancCuenta($this->encryption->encrypt($cuenta));
                $changed = true;
            }

            if ($changed) {
                $io->writeln('  Encriptado: ' . $client->getNombre());
                $count++;
            }
        }

        $this->em->flush();
        $io->success(sprintf('%d clientes actualizados.', $count));

        return Command::SUCCESS;
    }

    private function isEncrypted(string $value): bool
    {
        // Texto encriptado es base64 válido y más largo que un número de cuenta normal
        $decoded = base64_decode($value, true);
        return $decoded !== false && strlen($decoded) > 16 && strlen($value) > 40;
    }
}
