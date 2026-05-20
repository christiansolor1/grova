<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\LogActividad;
use App\Entity\User;
use App\Mail\CorreoAlertaSeguridad;
use App\Repository\LogActividadRepository;
use App\Repository\UserRepository;
use App\Service\Auth\ServicioGeolocalizacion;
use App\Service\Auth\ServicioLogActividad;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class EscuchadorLogin
{
    public function __construct(
        private readonly ServicioLogActividad $log,
        private readonly UserRepository $userRepo,
        private readonly NotificationService $notif,
        private readonly LogActividadRepository $logRepo,
        private readonly ServicioGeolocalizacion $geo,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $router,
    ) {
    }

    #[AsEventListener(event: LoginFailureEvent::class)]
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $email = $event->getPassport()?->getUser()?->getUserIdentifier()
            ?? $event->getRequest()->request->get('username', 'unknown');

        $this->log->registrar(
            accion: LogActividad::ACCION_LOGIN_FALLIDO,
            email: $email,
        );

        // Si el email no corresponde a un usuario real, no podemos notificar
        $user = $this->userRepo->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            return;
        }

        // Contar fallos recientes (últimos 15 min)
        $count = $this->contarFallosRecientes($user);

        // Solo notificar desde el 3er intento para evitar spam
        if ($count < 3) {
            return;
        }

        // Notificación en la campana
        $locale = $user->getPreferredLocale() ?? 'es';
        $this->notif->notify(
            user: $user,
            title: 'Intentos fallidos de inicio de sesión',
            body: sprintf('%d intentos fallidos en los últimos 15 minutos', $count),
            url: $this->router->generate('grova_profile', ['_locale' => $locale]),
            module: 'core',
            icon: 'bi-shield-exclamation',
            type: 'danger',
        );

        // Email de alerta
        $ip = $event->getRequest()->getClientIp() ?? '0.0.0.0';
        $ua = $event->getRequest()->headers->get('User-Agent') ?? '';

        try {
            $this->mailer->send(new CorreoAlertaSeguridad($user, 'Login fallido', [
                'ip'        => $ip,
                'userAgent' => $ua,
                'ubicacion' => $this->geo->localizar($ip),
                'intentos'  => $count,
            ]));
        } catch (\Throwable) {
            // No romper el login por fallo de correo
        }
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Saltar si es impersonación (switch_user)
        $request = $event->getRequest();
        if ($request->attributes->get('_switch_user')) {
            // Registrar el login pero sin notificar
            $sessionToken = $this->log->registrar(
                accion: LogActividad::ACCION_LOGIN_EXITOSO,
                usuario: $user,
                email: $user->getEmail(),
            );
            if ($sessionToken !== null && $request->hasSession()) {
                $request->getSession()->set('_session_token', $sessionToken);
            }

            return;
        }

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $ua = $request->headers->get('User-Agent') ?? '';

        // Verificar antes de registrar si es un dispositivo/IP nuevo
        $esNuevo = $this->esDispositivoNuevo($user, $ip, $ua);

        // Registrar el login
        $sessionToken = $this->log->registrar(
            accion: LogActividad::ACCION_LOGIN_EXITOSO,
            usuario: $user,
            email: $user->getEmail(),
        );

        if ($sessionToken !== null && $request->hasSession()) {
            $request->getSession()->set('_session_token', $sessionToken);
        }

        // Notificar solo si es un dispositivo que nunca había iniciado sesión
        if ($esNuevo) {
            $locale = $user->getPreferredLocale() ?? 'es';
            $ubicacion = $this->geo->localizar($ip);

            $this->notif->notify(
                user: $user,
                title: 'Nuevo inicio de sesión',
                body: sprintf('Desde %s — %s', $ip, self::parseDeviceName($ua)),
                url: $this->router->generate('grova_profile', ['_locale' => $locale]),
                module: 'core',
                icon: 'bi-laptop',
                type: 'info',
            );

            try {
                $this->mailer->send(new CorreoAlertaSeguridad($user, 'Nuevo dispositivo', [
                    'ip'        => $ip,
                    'userAgent' => $ua,
                    'ubicacion' => $ubicacion,
                ]));
            } catch (\Throwable) {
                // No romper el login por fallo de correo
            }
        }
    }

    private function esDispositivoNuevo(User $user, string $ip, string $ua): bool
    {
        $conn = $this->logRepo->getEntityManager()->getConnection();

        $existe = (int) $conn->fetchOne(
            'SELECT 1 FROM log_actividad WHERE usuario_id = :uid AND accion IN (:login, :fa2) AND ip = :ip AND user_agent = :ua LIMIT 1',
            [
                'uid'   => $user->getId(),
                'login' => LogActividad::ACCION_LOGIN_EXITOSO,
                'fa2'   => LogActividad::ACCION_2FA_EXITOSO,
                'ip'    => $ip,
                'ua'    => mb_substr($ua, 0, 512),
            ],
        );

        return $existe === 0;
    }

    private function contarFallosRecientes(User $user): int
    {
        $conn = $this->logRepo->getEntityManager()->getConnection();

        return (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM log_actividad WHERE usuario_id = :uid AND accion = :acc AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
            [
                'uid' => $user->getId(),
                'acc' => LogActividad::ACCION_LOGIN_FALLIDO,
            ],
        );
    }

    private static function parseDeviceName(string $ua): string
    {
        $lua = mb_strtolower($ua);
        if (str_contains($lua, 'iphone')) return 'iPhone';
        if (str_contains($lua, 'ipad')) return 'iPad';
        if (str_contains($lua, 'mac os')) return 'Mac';
        if (str_contains($lua, 'windows')) return 'Windows';
        if (str_contains($lua, 'android')) return 'Android';
        if (str_contains($lua, 'linux')) return 'Linux';

        return mb_substr($ua, 0, 60);
    }
}
