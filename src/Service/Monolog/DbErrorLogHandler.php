<?php

declare(strict_types=1);

namespace App\Service\Monolog;

use App\Entity\ErrorLog;
use App\Entity\Notification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

final class DbErrorLogHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        Level $level = Level::Warning,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->em->isOpen()) {
            return;
        }

        $exception = $record->context['exception'] ?? null;

        $errorLog = new ErrorLog();
        $errorLog->setLevel($record->level->getName());
        $errorLog->setChannel($record->channel);
        $errorLog->setMessage($this->truncar((string) $record->message, 5000));

        // Context sin la exception (la guardamos en campos aparte)
        $contextSinException = $record->context;
        unset($contextSinException['exception']);
        $errorLog->setContext(!empty($contextSinException) ? $contextSinException : null);
        $errorLog->setExtra(!empty($record->extra) ? $record->extra : null);

        if ($exception instanceof \Throwable) {
            $errorLog->setExceptionClass($exception::class);
            $errorLog->setFile($exception->getFile());
            $errorLog->setLine($exception->getLine());
            $errorLog->setTrace($exception->getTraceAsString());
        } elseif (\is_array($exception)) {
            $errorLog->setExceptionClass($exception['class'] ?? null);
            $errorLog->setFile($exception['file'] ?? null);
            $errorLog->setLine(isset($exception['line']) ? (int) $exception['line'] : null);
            $errorLog->setTrace($exception['trace'] ?? null);
        }

        // Intentar extraer tenant_id / user_id / url del contexto
        if (isset($contextSinException['tenant_id'])) {
            $errorLog->setTenantId((int) $contextSinException['tenant_id']);
        }
        if (isset($contextSinException['user_id'])) {
            $errorLog->setUserId((int) $contextSinException['user_id']);
        }
        if (isset($contextSinException['url'])) {
            $errorLog->setUrl((string) $contextSinException['url']);
        }

        $this->em->persist($errorLog);

        // Notificar a admins si es CRITICAL o ERROR
        // Monolog levels: DEBUG=100, INFO=200, WARNING=300, ERROR=400, CRITICAL=500
        if ($record->level->value >= Level::Error->value) {
            $this->agregarNotificaciones($errorLog);
        }

        $this->em->flush();
    }

    private function agregarNotificaciones(ErrorLog $errorLog): void
    {
        try {
            $admins = $this->userRepository->findByRole('ROLE_SUPER_ADMIN');
        } catch (\Throwable) {
            return; // Si falla la consulta, no notificamos pero el error se guarda igual
        }

        foreach ($admins as $admin) {
            $notificacion = new Notification();
            $notificacion->setUser($admin)
                ->setTitle('[' . $errorLog->getLevel() . '] ' . $this->truncar($errorLog->getMessage(), 120))
                ->setBody(sprintf(
                    "%s · %s\n%s%s",
                    $errorLog->getChannel(),
                    $errorLog->getCreatedAt()->format('Y-m-d H:i:s'),
                    $errorLog->getExceptionClass() ? $errorLog->getExceptionClass() . ': ' : '',
                    $errorLog->getFile() ? $errorLog->getFile() . ':' . $errorLog->getLine() : 'Sin trace',
                ))
                ->setUrl('/es/admin/errores/' . $errorLog->getId())
                ->setModule('system')
                ->setIcon('bug')
                ->setType('danger')
                ->setContext([
                    'error_log_id' => $errorLog->getId(),
                    'level' => $errorLog->getLevel(),
                ]);

            $this->em->persist($notificacion);
        }
    }

    private function truncar(string $texto, int $max): string
    {
        return mb_strlen($texto) > $max ? mb_substr($texto, 0, $max) . '…' : $texto;
    }
}
