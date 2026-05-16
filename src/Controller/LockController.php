<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserLockRepository;
use App\Service\SectionLockService;
use Doctrine\ORM\EntityManagerInterface;
use lbuchs\WebAuthn\WebAuthn;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lock', name: 'grova_lock_')]
final class LockController extends AbstractController
{
    public function __construct(
        private readonly SectionLockService $lockService,
        private readonly UserLockRepository $lockRepo,
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    // ── Pantalla de bloqueo ──────────────────────────────────────────────────

    #[Route('/screen/{section}', name: 'screen', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function screen(string $section, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $lock = $this->lockRepo->findOneBy(['user' => $user]);

        if ($lock === null || !in_array($section, $lock->getLockedSections(), true)) {
            return $this->redirect($request->query->get('redirect', $this->generateUrl('workspace', ['_locale' => $request->getLocale()])));
        }

        return $this->render('lock/screen.html.twig', [
            'section'           => $section,
            'section_label'     => SectionLockService::SECTIONS[$section] ?? $section,
            'redirect'          => $request->query->get('redirect', ''),
            'has_webauthn'      => $lock->isWebauthnEnabled(),
            'has_pin'           => $lock->hasPin(),
            'auto_prompt'       => $lock->isWebauthnEnabled(),
        ]);
    }

    // ── Verificar PIN ────────────────────────────────────────────────────────

    #[Route('/screen/{section}/pin', name: 'pin_verify', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function pinVerify(string $section, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('lock_pin_' . $section, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_lock_screen', ['_locale' => $request->getLocale(), 'section' => $section]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $lock = $this->lockRepo->findOneBy(['user' => $user]);
        $pin  = (string) $request->request->get('pin', '');

        if ($lock !== null && $lock->verifyPin($pin)) {
            $this->lockService->unlockSection($request->getSession(), $section);
            $redirect = (string) $request->request->get('redirect', '');
            return $this->redirect($redirect ?: $this->generateUrl('workspace', ['_locale' => $request->getLocale()]));
        }

        $this->addFlash('danger', 'PIN incorrecto.');
        return $this->redirectToRoute('grova_lock_screen', [
            '_locale'  => $request->getLocale(),
            'section'  => $section,
            'redirect' => $request->request->get('redirect', ''),
        ]);
    }

    // ── WebAuthn: challenge para desbloquear sección ─────────────────────────

    #[Route('/webauthn/challenge/{section}', name: 'webauthn_challenge', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function webauthnChallenge(string $section, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $lock = $this->lockRepo->findOneBy(['user' => $user]);

            if ($lock === null || !$lock->isWebauthnEnabled() || $lock->getWebauthnCredentialId() === null) {
                return new JsonResponse(['error' => 'WebAuthn no configurado'], 400);
            }

            $wa   = $this->buildWebAuthn($request);
            $args = $wa->getGetArgs(
                [base64_decode($lock->getWebauthnCredentialId())],
                20,
                true, true, true, true, true,
                true,
            );

            $request->getSession()->set('_wa_challenge_lock_' . $section, $wa->getChallenge()->getBinaryString());

            return new JsonResponse($args);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // ── WebAuthn: verificar para desbloquear sección ─────────────────────────

    #[Route('/webauthn/verify/{section}', name: 'webauthn_verify', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function webauthnVerify(string $section, Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $lock = $this->lockRepo->findOneBy(['user' => $user]);

            if ($lock === null || !$lock->isWebauthnEnabled()) {
                return new JsonResponse(['error' => 'WebAuthn no configurado'], 400);
            }

            $challenge = $request->getSession()->get('_wa_challenge_lock_' . $section);
            if ($challenge === null) {
                return new JsonResponse(['error' => 'Sesión expirada'], 400);
            }

            $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $wa = $this->buildWebAuthn($request);
            $wa->processGet(
                $this->b64Decode((string) ($data['clientDataJSON'] ?? '')),
                $this->b64Decode((string) ($data['authenticatorData'] ?? '')),
                $this->b64Decode((string) ($data['signature'] ?? '')),
                $lock->getWebauthnPublicKey(),
                $challenge,
                null,
                true,
                true,
            );

            $this->lockService->unlockSection($request->getSession(), $section);
            $request->getSession()->remove('_wa_challenge_lock_' . $section);

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ── WebAuthn: registrar credencial ───────────────────────────────────────

    #[Route('/webauthn/register/start', name: 'webauthn_register_start', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function webauthnRegisterStart(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->lockService->getOrCreate($user);

            $wa   = $this->buildWebAuthn($request);
            $args = $wa->getCreateArgs(
                (string) $user->getId(),
                $user->getUsername() ?? $user->getEmail() ?? 'user',
                $user->getNombreCompleto() ?: ($user->getUsername() ?? 'User'),
                20,
                false,
                true,
                false,   // solo autenticador de plataforma (Touch ID, Face ID)
            );

            $request->getSession()->set('_wa_challenge_reg', $wa->getChallenge()->getBinaryString());

            return new JsonResponse($args);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/webauthn/register/finish', name: 'webauthn_register_finish', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function webauthnRegisterFinish(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            $lock = $this->lockService->getOrCreate($user);

            $challenge = $request->getSession()->get('_wa_challenge_reg');
            if ($challenge === null) {
                return new JsonResponse(['error' => 'Sesión expirada, recarga e intenta de nuevo'], 400);
            }

            $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $wa   = $this->buildWebAuthn($request);

            $cred = $wa->processCreate(
                $this->b64Decode((string) ($data['clientDataJSON'] ?? '')),
                $this->b64Decode((string) ($data['attestationObject'] ?? '')),
                $challenge,
                true,
                true,
                false,
            );

            $this->lockService->saveWebAuthn(
                $lock,
                base64_encode($cred->credentialId),
                $cred->credentialPublicKey,
            );

            $request->getSession()->remove('_wa_challenge_reg');

            $response = new JsonResponse(['ok' => true]);
            $response->headers->setCookie(Cookie::create('grova_biometric', '1', strtotime('+1 year'), '/', null, true, false));
            return $response;
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // ── WebAuthn: eliminar credencial ────────────────────────────────────────

    #[Route('/webauthn/remove', name: 'webauthn_remove', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function webauthnRemove(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('lock_webauthn_remove', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
        } else {
            /** @var User $user */
            $user = $this->getUser();
            $lock = $this->lockRepo->findOneBy(['user' => $user]);
            if ($lock !== null) {
                $this->lockService->removeWebAuthn($lock);
                $this->addFlash('success', 'Credencial biométrica eliminada.');
            }
        }
        $response = $this->redirectToRoute('grova_profile', ['_locale' => $request->getLocale()]);
        $response->headers->clearCookie('grova_biometric', '/');
        return $response;
    }

    // ── Configuración: guardar ajustes ───────────────────────────────────────

    #[Route('/setup', name: 'setup', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function setup(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('lock_setup', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token inválido.');
            return $this->redirectToRoute('grova_profile', ['_locale' => $request->getLocale()]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $lock = $this->lockService->getOrCreate($user);

        $sections   = (array) $request->request->all('sections');
        $ttl        = (int) $request->request->get('ttl', 30);
        $autoPrompt = (bool) $request->request->get('webauthn_auto_prompt', false);
        $this->lockService->saveSettings($lock, $sections, $ttl, $autoPrompt);

        $pin = trim((string) $request->request->get('pin', ''));
        if ($pin !== '') {
            if (strlen($pin) < 4 || strlen($pin) > 8) {
                $this->addFlash('danger', 'El PIN debe tener entre 4 y 8 dígitos.');
                return $this->redirectToRoute('grova_profile', ['_locale' => $request->getLocale()]);
            }
            $this->lockService->setPin($lock, $pin);
        }

        $this->addFlash('success', 'Configuración de seguridad guardada.');
        return $this->redirectToRoute('grova_profile', ['_locale' => $request->getLocale()]);
    }

    // ── LOGIN BIOMÉTRICO (público) ───────────────────────────────────────────

    #[Route('/login-webauthn/challenge', name: 'login_webauthn_challenge', methods: ['GET'])]
    public function loginWebauthnChallenge(Request $request): JsonResponse
    {
        try {
            $wa   = $this->buildWebAuthn($request);
            $args = $wa->getGetArgs([], 20, true, true, true, true, true, true);
            $request->getSession()->set('_wa_challenge_login', $wa->getChallenge()->getBinaryString());
            return new JsonResponse($args);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/login-webauthn/verify', name: 'login_webauthn_verify', methods: ['POST'])]
    public function loginWebauthnVerify(Request $request): JsonResponse
    {
        try {
            $challenge = $request->getSession()->get('_wa_challenge_login');
            if ($challenge === null) {
                return new JsonResponse(['error' => 'Sesión expirada'], 400);
            }

            $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $credentialId = base64_encode($this->b64Decode((string) ($data['id'] ?? '')));
            $lock         = $this->lockRepo->findByCredentialId($credentialId);

            if ($lock === null || !$lock->isWebauthnEnabled()) {
                return new JsonResponse(['error' => 'Credencial no encontrada'], 401);
            }

            $wa = $this->buildWebAuthn($request);
            $wa->processGet(
                $this->b64Decode((string) ($data['clientDataJSON'] ?? '')),
                $this->b64Decode((string) ($data['authenticatorData'] ?? '')),
                $this->b64Decode((string) ($data['signature'] ?? '')),
                $lock->getWebauthnPublicKey(),
                $challenge,
                null,
                true,
                true,
            );

            $request->getSession()->remove('_wa_challenge_login');

            $user  = $lock->getUser();
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);
            $request->getSession()->migrate(true);
            $request->getSession()->set('_security_main', serialize($token));

            $response = new JsonResponse([
                'ok'       => true,
                'redirect' => $this->generateUrl('workspace', ['_locale' => $request->getLocale()]),
            ]);
            $response->headers->setCookie(Cookie::create('grova_biometric', '1', strtotime('+1 year'), '/', null, true, false));
            return $response;
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /** Decode standard base64 or base64url (URL-safe) strings to binary. */
    private function b64Decode(string $s): string
    {
        $s   = str_replace(['-', '_'], ['+', '/'], $s);
        $pad = strlen($s) % 4;
        if ($pad !== 0) {
            $s .= str_repeat('=', 4 - $pad);
        }
        return (string) base64_decode($s, true);
    }

    private function buildWebAuthn(Request $request): WebAuthn
    {
        $rpId = $this->getRpId($request);
        // 4º parámetro true = usar base64url en la serialización JSON
        return new WebAuthn('Grova', $rpId, null, true);
    }

    private function getRpId(Request $request): string
    {
        // WebAuthn solo permite HTTP para rpId='localhost'.
        // Para cualquier subdominio de .localhost (ej: grova.localhost) en HTTP,
        // se usa 'localhost' como rpId — es válido porque el spec permite rpId = suffix del origin.
        // En HTTPS se usa el host real.
        if ($request->getScheme() === 'https') {
            return $request->getHost();
        }

        $host = $request->getHost();

        // grova.localhost, app.localhost, etc. → localhost
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return 'localhost';
        }

        // Cualquier otro host HTTP no funcionará con WebAuthn (requiere HTTPS)
        return $host;
    }
}
