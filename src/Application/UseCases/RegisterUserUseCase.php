<?php
declare(strict_types=1);

namespace GripAndGrin\Application\UseCases;

use DateTime;
use GripAndGrin\Domain\Entities\User;
use GripAndGrin\Domain\Interfaces\UserRepositoryInterface;
use GripAndGrin\Domain\ValueObjects\Email;
use GripAndGrin\Domain\ValueObjects\Password;
use InvalidArgumentException;

class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(string $username, string $email, string $password): User
    {
        $username = trim($username);
        if (empty($username) || strlen($username) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters long');
        }

        $emailVO = new Email($email);
        $passwordVO = new Password($password);

        if ($this->userRepository->emailExists($emailVO->getValue())) {
            throw new InvalidArgumentException('Email already exists');
        }

        if ($this->userRepository->usernameExists($username)) {
            throw new InvalidArgumentException('Username already exists');
        }

        $user = new User(
            0,
            $username,
            $emailVO->getValue(),
            $passwordVO->getHashedValue(),
            new DateTime(),
            true
        );

        return $this->userRepository->save($user);
    }
}
