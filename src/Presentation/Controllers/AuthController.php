<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\AuthenticateUserUseCase;
use GripAndGrin\Application\UseCases\RegisterUserUseCase;
use GripAndGrin\Infrastructure\Services\SessionService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AuthController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly AuthenticateUserUseCase $authenticateUserUseCase,
        private readonly RegisterUserUseCase $registerUserUseCase,
        private readonly SessionService $sessionService
    ) {}

    public function showLogin(): Response
    {
        if ($this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/');
        }

        $content = $this->twig->render('auth/login.html.twig', [
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }

    public function login(Request $request): Response
    {
        if ($this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/');
        }

        if ($request->getMethod() !== 'POST') {
            return new RedirectResponse('/login');
        }

        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('csrf_token', '');

        try {
            // Validate CSRF token
            if (!$this->sessionService->validateCsrfToken($csrfToken)) {
                throw new InvalidArgumentException('Invalid security token');
            }

            $user = $this->authenticateUserUseCase->execute($email, $password);
            $this->sessionService->login($user);

            return new RedirectResponse('/');
        } catch (InvalidArgumentException $e) {
            $content = $this->twig->render('auth/login.html.twig', [
                'error' => $e->getMessage(),
                'email' => $email,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content, 400);
        }
    }

    public function showRegister(): Response
    {
        if ($this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/');
        }

        $content = $this->twig->render('auth/register.html.twig', [
            'csrf_token' => $this->sessionService->generateCsrfToken()
        ]);
        return new Response($content);
    }

    public function register(Request $request): Response
    {
        if ($this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/');
        }

        if ($request->getMethod() !== 'POST') {
            return new RedirectResponse('/register');
        }

        $username = $request->request->get('username', '');
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $confirmPassword = $request->request->get('confirm_password', '');
        $csrfToken = $request->request->get('csrf_token', '');

        try {
            // Validate CSRF token
            if (!$this->sessionService->validateCsrfToken($csrfToken)) {
                throw new InvalidArgumentException('Invalid security token');
            }

            // Validate password confirmation
            if ($password !== $confirmPassword) {
                throw new InvalidArgumentException('Passwords do not match');
            }

            $user = $this->registerUserUseCase->execute($username, $email, $password);
            $this->sessionService->login($user);

            return new RedirectResponse('/');
        } catch (InvalidArgumentException $e) {
            $content = $this->twig->render('auth/register.html.twig', [
                'error' => $e->getMessage(),
                'username' => $username,
                'email' => $email,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content, 400);
        }
    }

    public function logout(): Response
    {
        $this->sessionService->logout();
        return new RedirectResponse('/');
    }
}
