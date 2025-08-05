<?php
declare(strict_types=1);

namespace GripAndGrin\Presentation\Controllers;

use GripAndGrin\Application\UseCases\GetUserProfileUseCase;
use GripAndGrin\Application\UseCases\UpdateUserProfileUseCase;
use GripAndGrin\Infrastructure\Services\SessionService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ProfileController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SessionService $sessionService,
        private readonly GetUserProfileUseCase $getUserProfileUseCase,
        private readonly UpdateUserProfileUseCase $updateUserProfileUseCase
    ) {}

    public function show(): Response
    {
        if (!$this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/login');
        }

        $userId = $this->sessionService->getCurrentUserId();

        try {
            $user = $this->getUserProfileUseCase->execute($userId);

            $content = $this->twig->render('profile/show.html.twig', [
                'user' => $user,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content);
        } catch (InvalidArgumentException $e) {
            return new RedirectResponse('/login');
        }
    }

    public function edit(): Response
    {
        if (!$this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/login');
        }

        $userId = $this->sessionService->getCurrentUserId();

        try {
            $user = $this->getUserProfileUseCase->execute($userId);

            $content = $this->twig->render('profile/edit.html.twig', [
                'user' => $user,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content);
        } catch (InvalidArgumentException $e) {
            return new RedirectResponse('/login');
        }
    }

    public function update(Request $request): Response
    {
        if (!$this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/login');
        }

        if ($request->getMethod() !== 'POST') {
            return new RedirectResponse('/profile/edit');
        }

        $userId = $this->sessionService->getCurrentUserId();
        $username = $request->request->get('username', '');
        $email = $request->request->get('email', '');
        $firstName = $request->request->get('first_name', '');
        $lastName = $request->request->get('last_name', '');
        $bio = $request->request->get('bio', '');
        $csrfToken = $request->request->get('csrf_token', '');

        try {
            // Validate CSRF token
            if (!$this->sessionService->validateCsrfToken($csrfToken)) {
                throw new InvalidArgumentException('Invalid security token');
            }

            $user = $this->updateUserProfileUseCase->execute(
                $userId,
                $username,
                $email,
                $firstName ?: null,
                $lastName ?: null,
                $bio ?: null
            );

            // Update session data
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['email'] = $user->getEmail();

            return new RedirectResponse('/profile?updated=1');
        } catch (InvalidArgumentException $e) {
            $user = $this->getUserProfileUseCase->execute($userId);

            $content = $this->twig->render('profile/edit.html.twig', [
                'error' => $e->getMessage(),
                'user' => $user,
                'username' => $username,
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'bio' => $bio,
                'csrf_token' => $this->sessionService->generateCsrfToken()
            ]);
            return new Response($content, 400);
        }
    }
}
