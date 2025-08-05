<?php
declare(strict_types=1);

namespace GripAndGrin\Infrastructure\Middleware;

use GripAndGrin\Infrastructure\Services\SessionService;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function handle(): ?Response
    {
        // Check if user is logged in
        if (!$this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/login');
        }

        // Get current user
        $userId = $this->sessionService->getCurrentUserId();
        $user = $this->userRepository->findById($userId);

        // Check if user exists and can manage articles
        if (!$user || !$user->canManageArticles()) {
            return new Response('Access denied. Admin or Editor role required.', 403);
        }

        return null; // Allow access
    }

    public function requireAdmin(): ?Response
    {
        // Check if user is logged in
        if (!$this->sessionService->isLoggedIn()) {
            return new RedirectResponse('/login');
        }

        // Get current user
        $userId = $this->sessionService->getCurrentUserId();
        $user = $this->userRepository->findById($userId);

        // Check if user exists and is admin
        if (!$user || !$user->isAdmin()) {
            return new Response('Access denied. Admin role required.', 403);
        }

        return null; // Allow access
    }
}
