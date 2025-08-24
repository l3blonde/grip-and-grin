<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Email;
use GripAndGrin\Domain\ValueObjects\Password;
use GripAndGrin\Domain\Entities\User;
use DateTime;

class CreateUserUseCase
{
    private UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function execute(string $username, string $email, string $password, string $role = 'editor'): ?User
    {
        $emailVO = new Email($email);
        $passwordVO = new Password($password);

        if ($this->userRepository->emailExists($emailVO->getValue())) {
            throw new \InvalidArgumentException('Email already exists');
        }

        $user = new User(
            null, // New user, no ID yet
            $username,
            $emailVO->getValue(),
            $passwordVO->getHash(),
            $role,
            true, // Active by default
            false, // Email not verified
            null, // No verification token
            null, // No reset token
            null, // No reset expires
            null, // No first name
            null, // No last name
            null, // No bio
            null, // No avatar
            new DateTime(),
            new DateTime()
        );

        return $this->userRepository->save($user);
    }
}
