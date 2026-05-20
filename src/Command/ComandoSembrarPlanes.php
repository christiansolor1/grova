<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Plan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'grova:sembrar-planes',
    description: 'Crea los planes base (Trial, Pro, Enterprise) si no existen.',
)]
final class ComandoSembrarPlanes extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(Plan::class);

        $planes = [
            [
                'nombre'        => 'Trial',
                'modulos'       => [],          // sin módulos verticales — el tenant elige al activar
                'precioMensual' => '0.00',
                'estado'        => 'activo',
            ],
            [
                'nombre'        => 'Pro',
                'modulos'       => [],
                'precioMensual' => '29.99',
                'estado'        => 'activo',
            ],
            [
                'nombre'        => 'Enterprise',
                'modulos'       => [],
                'precioMensual' => '99.99',
                'estado'        => 'activo',
            ],
        ];

        foreach ($planes as $datos) {
            $existente = $repo->findOneBy(['nombre' => $datos['nombre']]);

            if ($existente instanceof Plan) {
                $io->writeln(sprintf('  <comment>~</comment> Plan "%s" ya existe, se omite.', $datos['nombre']));
                continue;
            }

            $plan = new Plan();
            $plan->setNombre($datos['nombre'])
                 ->setModulos($datos['modulos'])
                 ->setPrecioMensual($datos['precioMensual'])
                 ->setEstado($datos['estado']);

            $this->em->persist($plan);
            $io->writeln(sprintf('  <info>+</info> Plan "%s" creado.', $datos['nombre']));
        }

        $this->em->flush();
        $io->success('Planes sembrados.');

        return Command::SUCCESS;
    }
}
