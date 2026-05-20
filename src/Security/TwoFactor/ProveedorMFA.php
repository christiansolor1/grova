<?php

declare(strict_types=1);

namespace App\Security\TwoFactor;

use App\Entity\LogActividad;
use App\Entity\User;
use App\Entity\UserCodigoEmail2FA;
use App\Mail\CorreoAlertaSeguridad;
use App\Repository\UserCredencialBiometricaRepository;
use App\Repository\UserLockRepository;
use App\Service\Auth\ServicioCodigoEmail2FA;
use App\Service\Auth\ServicioGeolocalizacion;
use App\Service\Auth\ServicioLogActividad;
use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class ProveedorMFA implements TwoFactorProviderInterface
{
    public const WA_TOKEN_PREFIX    = 'WA:';
    public const WA_SESSION_KEY     = '_mfa_wa_token';
    public const ALERT_SENT_SESSION = '_mfa_alert_sent';

    public function __construct(
        private readonly UserCredencialBiometricaRepository $credencialRepo,
        private readonly UserLockRepository $userLockRepo,
        private readonly FormularioMFA $formulario,
        private readonly RequestStack $requestStack,
        private readonly RateLimiterFactory $mfaAttemptLimiter,
        private readonly ServicioCodigoEmail2FA $servicioCodigoEmail,
        private readonly MailerInterface $mailer,
        private readonly ServicioLogActividad $log,
        private readonly ServicioGeolocalizacion $geo,
    ) {
    }

    public function beginAuthentication(AuthenticationContextInterface $context): bool
    {
        $user = $context->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->isTotpAuthenticationEnabled() && $user->isTotp2faEnabled()) {
            return true;
        }

        if ($user->isWebauthn2faEnabled() && count($this->credencialRepo->findByUser($user)) > 0) {
            return true;
        }

        if ($user->isPin2faEnabled()) {
            $userLock = $this->userLockRepo->findOneBy(['user' => $user]);
            if ($userLock !== null && $userLock->hasPin()) {
                return true;
            }
        }

        // Ruta Email
        if ($user->isEmail2faEnabled()) {
            return true;
        }

        return false;
    }

    public function prepareAuthentication(object $user): void
    {
        // Sin preparación necesaria
    }

    public function validateAuthenticationCode(object $user, string $authenticationCode): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        // Ruta WebAuthn (token de un solo uso — no necesita rate limiting)
        if (str_starts_with($authenticationCode, self::WA_TOKEN_PREFIX)) {
            return $this->validarTokenWebAuthn($user, substr($authenticationCode, strlen(self::WA_TOKEN_PREFIX)));
        }

        // Ruta Email (sin rate limiting — el código expira en 5 min)
        if ($user->isEmail2faEnabled()) {
            if ($this->servicioCodigoEmail->validarCodigo($user, $authenticationCode)) {
                return true;
            }
            $this->log->registrar(
                accion: LogActividad::ACCION_2FA_FALLIDO,
                usuario: $user,
                email: $user->getEmail(),
                detalles: ['motivo' => 'codigo_invalido', 'metodo' => 'email'],
            );
        }

        // Rate limiting para TOTP y PIN
        $limiter = $this->mfaAttemptLimiter->create((string) $user->getId());
        if (!$limiter->consume(1)->isAccepted()) {
            $this->enviarAlertaBloqueo($user);
            $this->log->registrar(
                accion: LogActividad::ACCION_2FA_FALLIDO,
                usuario: $user,
                email: $user->getEmail(),
                detalles: ['motivo' => 'rate_limited', 'metodo' => 'totp_pin'],
            );
            return false;
        }

        // Ruta TOTP
        if ($user->isTotpAuthenticationEnabled()) {
            $secreto = $user->getTotpSecret();
            if ($secreto !== null) {
                $totp = TOTP::createFromSecret($secreto);
                $totp->setPeriod(30);

                if ($totp->verify($authenticationCode, null, 1)) {
                    $limiter->reset(); // éxito: limpia intentos fallidos
                    $this->log->registrar(
                        accion: LogActividad::ACCION_2FA_EXITOSO,
                        usuario: $user,
                        email: $user->getEmail(),
                        detalles: ['metodo' => 'totp'],
                    );
                    return true;
                }
                $this->log->registrar(
                    accion: LogActividad::ACCION_2FA_FALLIDO,
                    usuario: $user,
                    email: $user->getEmail(),
                    detalles: ['motivo' => 'codigo_invalido', 'metodo' => 'totp'],
                );
            }
        }

        // Ruta PIN (fallback)
        $userLock = $this->userLockRepo->findOneBy(['user' => $user]);
        if ($userLock !== null && $userLock->hasPin() && $userLock->verifyPin($authenticationCode)) {
            $limiter->reset(); // éxito: limpia intentos fallidos
            $this->log->registrar(
                accion: LogActividad::ACCION_2FA_EXITOSO,
                usuario: $user,
                email: $user->getEmail(),
                detalles: ['metodo' => 'pin'],
            );
            return true;
        }
        if ($userLock !== null && $userLock->hasPin()) {
            $this->log->registrar(
                accion: LogActividad::ACCION_2FA_FALLIDO,
                usuario: $user,
                email: $user->getEmail(),
                detalles: ['motivo' => 'codigo_invalido', 'metodo' => 'pin'],
            );
        }

        return false;
    }

    public function getFormRenderer(): TwoFactorFormRendererInterface
    {
        return $this->formulario;
    }

    private function validarTokenWebAuthn(User $user, string $token): bool
    {
        $session = $this->requestStack->getSession();
        $datos   = $session->get(self::WA_SESSION_KEY);

        if (!is_array($datos)) {
            return false;
        }

        if (($datos['token'] ?? '') !== $token) {
            return false;
        }

        if (($datos['user_id'] ?? null) !== $user->getId()) {
            return false;
        }

        $session->remove(self::WA_SESSION_KEY);

        return true;
    }

    private function enviarAlertaBloqueo(User $user): void
    {
        $session = $this->requestStack->getSession();
        if ($session === null) {
            return;
        }

        $session->getFlashBag()->add('error', 'Has superado el límite de intentos. Espera 10 minutos para volver a intentar.');

        if ($session->has(self::ALERT_SENT_SESSION)) {
            return; // ya se envió alerta en este bloqueo
        }

        try {
            $request  = $this->requestStack->getCurrentRequest();
            $detalles = [];

            if ($request !== null) {
                $ip           = $request->getClientIp() ?? '0.0.0.0';
                $detalles['ip']        = $ip;
                $detalles['userAgent'] = $request->headers->get('User-Agent');
                $detalles['ubicacion'] = $this->geo->localizar($ip);
            }

            $email = new CorreoAlertaSeguridad($user, 'Código TOTP / PIN', $detalles);
            $this->mailer->send($email);
            $session->set(self::ALERT_SENT_SESSION, true);
        } catch (\Throwable) {
            // Si falla el envío, no bloquear al usuario
        }
    }
}
