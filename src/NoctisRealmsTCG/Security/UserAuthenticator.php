<?php declare(strict_types=1);

namespace App\Security;

use App\Model\Modules\User\UserFacade;
use Nette\Security\Authenticator;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

class UserAuthenticator implements Authenticator
{

    private UserFacade $userFacade;

    private Passwords $passwords;

    public function __construct(UserFacade $userFacade, Passwords $passwords)
    {
        $this->userFacade = $userFacade;
        $this->passwords = $passwords;
    }

    public function authenticate(string $email, string $password): SimpleIdentity
    {
        $u = $this->userFacade->findByEmail($email);
        if (!$u || !$this->passwords->verify($password, $u->getPassword())) {
            throw new AuthenticationException('Neplatný e-mail nebo heslo.');
        }

        return new SimpleIdentity(
            $u->getId(),
            [$u->getRole()->getSlug()],
            ['email' => $u->getEmail()]
        );
    }
}