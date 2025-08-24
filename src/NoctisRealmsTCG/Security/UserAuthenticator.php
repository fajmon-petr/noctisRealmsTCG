<?php declare(strict_types=1);

namespace App\Security;

use App\Model\Service\UserService;
use Nette\Security\Authenticator;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

class UserAuthenticator implements Authenticator
{
    public function __construct(
        private UserService $users,
        private Passwords $passwords,
    ) {}

    public function authenticate(string $email, string $password): SimpleIdentity
    {
        $u = $this->users->findByEmail($email);
        if (!$u || !$this->passwords->verify($password, $u->getPassword())) {
            throw new AuthenticationException('Neplatný e-mail nebo heslo.');
        }
        return new SimpleIdentity($u->getId(), [], ['email' => $u->getEmail()]);
    }
}