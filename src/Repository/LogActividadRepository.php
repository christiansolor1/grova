<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LogActividad;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogActividad>
 */
final class LogActividadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogActividad::class);
    }

    /**
     * Devuelve los inicios de sesión exitosos de un usuario, agrupando por dispositivo/IP.
     *
     * @return array<int, array{ip: string, userAgent: string, ultimaVez: \DateTimeImmutable, count: int}>
     */
    public function findSesionesRecientes(User $usuario, int $limite = 20): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT l.ip, l.user_agent, MAX(l.created_at) as ultima_vez, COUNT(*) as count
            FROM log_actividad l
            WHERE l.usuario_id = :userId
              AND l.accion IN (:accion1, :accion2)
              AND l.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY l.ip, l.user_agent
            ORDER BY ultima_vez DESC
            LIMIT :limite
        SQL;

        $stmt = $conn->executeQuery($sql, [
            'userId'  => $usuario->getId(),
            'accion1' => LogActividad::ACCION_LOGIN_EXITOSO,
            'accion2' => LogActividad::ACCION_2FA_EXITOSO,
            'limite'  => $limite,
        ], [
            'userId' => ParameterType::INTEGER,
            'limite' => ParameterType::INTEGER,
        ]);

        $rows = $stmt->fetchAllAssociative();

        return array_map(function (array $row): array {
            $ultimaVez = $row['ultima_vez'];
            if (is_string($ultimaVez)) {
                $ultimaVez = new \DateTimeImmutable($ultimaVez);
            }

            return [
                'ip'        => $row['ip'],
                'userAgent' => $row['user_agent'] ?? '',
                'ultimaVez' => $ultimaVez,
                'count'     => (int) $row['count'],
            ];
        }, $rows);
    }

    /**
     * Devuelve registros individuales de sesiones activas (no agrupados), incluyendo
     * el sessionToken para permitir revocación por dispositivo.
     *
     * @return array<int, array{id: int, ip: string, userAgent: string, ultimaVez: \DateTimeImmutable, sessionToken: ?string}>
     */
    public function findSesionesIndividuales(User $usuario, int $limite = 30): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT l.id, l.ip, l.user_agent, l.created_at as ultima_vez, l.session_token
            FROM log_actividad l
            WHERE l.usuario_id = :userId
              AND l.accion IN (:accion1, :accion2)
              AND l.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
              AND l.session_token IS NOT NULL
            ORDER BY l.created_at DESC
            LIMIT :limite
        SQL;

        $stmt = $conn->executeQuery($sql, [
            'userId'  => $usuario->getId(),
            'accion1' => LogActividad::ACCION_LOGIN_EXITOSO,
            'accion2' => LogActividad::ACCION_2FA_EXITOSO,
            'limite'  => $limite,
        ], [
            'userId' => ParameterType::INTEGER,
            'limite' => ParameterType::INTEGER,
        ]);

        $rows = $stmt->fetchAllAssociative();

        return array_map(function (array $row): array {
            $ultimaVez = $row['ultima_vez'];
            if (is_string($ultimaVez)) {
                $ultimaVez = new \DateTimeImmutable($ultimaVez);
            }

            return [
                'id'           => (int) $row['id'],
                'ip'           => $row['ip'],
                'userAgent'    => $row['user_agent'] ?? '',
                'ultimaVez'    => $ultimaVez,
                'sessionToken' => $row['session_token'],
            ];
        }, $rows);
    }

    /**
     * Devuelve eventos de seguridad relevantes: login fallido, 2FA fallido, cambio de contraseña.
     * NO incluye logins exitosos para no saturar la vista.
     *
     * @return array<int, array{id: int, accion: string, ip: string, userAgent: string, createdAt: \DateTimeImmutable, email: ?string}>
     */
    public function findActividadSeguridad(User $usuario, int $limite = 20): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT l.id, l.accion, l.ip, l.user_agent, l.created_at, l.email
            FROM log_actividad l
            WHERE l.usuario_id = :userId
              AND l.accion IN (:fallido, :fallido2fa, :cambio_pass)
              AND l.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            ORDER BY l.created_at DESC
            LIMIT :limite
        SQL;

        $stmt = $conn->executeQuery($sql, [
            'userId'       => $usuario->getId(),
            'fallido'      => LogActividad::ACCION_LOGIN_FALLIDO,
            'fallido2fa'   => LogActividad::ACCION_2FA_FALLIDO,
            'cambio_pass'  => LogActividad::ACCION_CAMBIO_CONTRASENA,
            'limite'       => $limite,
        ], [
            'userId' => ParameterType::INTEGER,
            'limite' => ParameterType::INTEGER,
        ]);

        $rows = $stmt->fetchAllAssociative();

        return array_map(function (array $row): array {
            $createdAt = $row['created_at'];
            if (is_string($createdAt)) {
                $createdAt = new \DateTimeImmutable($createdAt);
            }

            return [
                'id'        => (int) $row['id'],
                'accion'    => $row['accion'],
                'ip'        => $row['ip'],
                'userAgent' => $row['user_agent'] ?? '',
                'createdAt' => $createdAt,
                'email'     => $row['email'],
            ];
        }, $rows);
    }
}
