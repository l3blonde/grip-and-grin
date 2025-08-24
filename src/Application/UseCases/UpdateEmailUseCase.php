<?php

declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Email;
use InvalidArgumentException;

class UpdateEmailUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(int $userId, string $newEmail): bool
    {
        $emailVO = new Email($newEmail);

        if ($this->userRepository->emailExists($emailVO->getValue())) {
            throw new InvalidArgumentException('Email address already in use');
        }

        return $this->userRepository->updateEmail($userId, $emailVO->getValue());
    }
}
