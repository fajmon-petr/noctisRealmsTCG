<?php declare(strict_types=1);

namespace App\Model\Modules\User;

use App\Model\Entity\Role;
use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Security\Passwords;

class UserFacade
{
    private EntityManagerInterface $em;

    private Passwords $passwords;

    public function __construct(EntityManagerInterface $em, Passwords $passwords)
    {
        $this->em = $em;
        $this->passwords = $passwords;
    }

    public function register(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwords->hash($password));
        $user->setRole($this->em->getRepository(Role::class)->findOneBy(['slug' => 'player']));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }
}
