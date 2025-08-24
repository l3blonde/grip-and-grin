<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases\GDPR;

use GripAndGrin\Domain\Services\GDPR\GDPRService;

class HandleConsentUseCase
{
    private GDPRService $gdprService;

    public function __construct(GDPRService $gdprService)
    {
        $this->gdprService = $gdprService;
    }

    public function execute(int $userId, string $consentType, string $ipAddress, string $userAgent): bool
    {
        return $this->gdprService->recordConsent($userId, $consentType, $ipAddress, $userAgent);
    }
}
