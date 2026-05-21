<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\LogActividad;
use App\Entity\ModuloTenant;
use App\Entity\Suscripcion;
use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\PlanRepository;
use App\Repository\UserRepository;
use App\Service\Auth\ServicioLogActividad;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Psr\Log\LoggerInterface;

final class AuthenticatorGoogle extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly UserRepository $userRepository,
        private readonly PlanRepository $planRepository,
        private readonly EntityManagerInterface $emCore,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly RouterInterface $router,
        private readonly ServicioLogActividad $log,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = (string) $googleUser->getEmail();

                $usuario = $this->userRepository->findOneBy(['email' => $email]);

                if ($usuario instanceof User) {
                    if (!$usuario->isEmailVerificado()) {
                        $usuario->setEmailVerificado(true);
                        $this->emCore->flush();
                    }

                    return $usuario;
                }

                return $this->crearUsuarioDesdeGoogle($googleUser);
            }),
            [new RememberMeBadge()],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof User) {
            $this->log->registrar(LogActividad::ACCION_GOOGLE_LOGIN, $user, $user->getEmail());
        }

        return new RedirectResponse($this->router->generate('workspace', ['_locale' => 'es']));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error('Google OAuth failure: '.$exception->getMessage(), [
            'exception_class' => $exception::class,
            'trace' => $exception->getTraceAsString(),
        ]);
        $request->getSession()->getFlashBag()->add('error', 'No se pudo iniciar sesión con Google. Intenta de nuevo.');

        return new RedirectResponse($this->router->generate('login', ['_locale' => 'es']));
    }

    private function crearUsuarioDesdeGoogle(GoogleUser $googleUser): User
    {
        $plan = $this->planRepository->findOneBy(['nombre' => 'Pro', 'estado' => 'activo'])
            ?? throw new \RuntimeException('No existe un plan Pro activo.');

        $nombre = (string) ($googleUser->getFirstName() ?: $googleUser->getName() ?: 'Usuario');
        $apellido = (string) ($googleUser->getLastName() ?: '');
        $email = (string) $googleUser->getEmail();
        $slug = $this->generarSlugUnico('Workspace de '.$nombre);

        $dbName = (string) $this->emCore->getConnection()->getDatabase();

        $tenant = new Tenant();
        $tenant->setNombre('Workspace de '.$nombre)
               ->setSlug($slug)
               ->setDbName($dbName)
               ->setEstado('activo')
               ->setTipo('trial');
        $this->emCore->persist($tenant);

        $suscripcion = new Suscripcion();
        $suscripcion->setTenant($tenant)
                    ->setPlan($plan)
                    ->setFechaInicio(new \DateTimeImmutable('today'))
                    ->setFechaVencimiento(new \DateTimeImmutable('+30 days'))
                    ->setEstado('activa');
        $this->emCore->persist($suscripcion);

        foreach ($plan->getModulos() as $claveModulo) {
            $modulo = new ModuloTenant();
            $modulo->setTenant($tenant)
                   ->setModuloKey($claveModulo)
                   ->setActivo(true);
            $this->emCore->persist($modulo);
        }

        $esPrimero = $this->userRepository->count([]) === 0;
        $roles = $esPrimero ? ['ROLE_SUPER_ADMIN', 'ROLE_DEVELOPER'] : ['ROLE_USER'];

        $username = explode('@', $email)[0];

        $usuario = new User();
        $usuario->setEmail($email)
                ->setUsername($username)
                ->setNombre($nombre)
                ->setApellido($apellido)
                ->setRoles($roles)
                ->setEmailVerificado(true)
                ->setTenant($tenant)
                ->setAvatarUrl($googleUser->getAvatar())
                ->setPreferredLocale($googleUser->getLocale() !== null ? (str_starts_with($googleUser->getLocale(), 'es') ? 'es' : 'en') : null);

        $contrasenaTemporal = bin2hex(random_bytes(16));
        $usuario->setPassword($this->hasher->hashPassword($usuario, $contrasenaTemporal));

        $this->emCore->persist($usuario);
        $this->emCore->flush();

        return $usuario;
    }

    private function generarSlugUnico(string $nombreEmpresa): string
    {
        $slugger = new AsciiSlugger();
        $base = 'grova_'.strtolower((string) $slugger->slug($nombreEmpresa));
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'grova_workspace';
        $slug = $base;
        $sufijo = 1;

        while ($this->emCore->getRepository(Tenant::class)->findOneBy(['slug' => $slug]) instanceof Tenant) {
            $slug = $base.'_'.$sufijo;
            ++$sufijo;
        }

        return $slug;
    }
}
