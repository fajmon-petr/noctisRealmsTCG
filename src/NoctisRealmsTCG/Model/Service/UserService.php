<?php declare(strict_types=1);

namespace App\Model\Service;

use App\Model\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Security\Passwords;

final class UserService
{
    public function __construct(private EntityManagerInterface $em, private Passwords $passwords) {}

    public function register(string $email, string $password): User
    {
        $u = new User();
        $u->setEmail($email);
        $u->setPassword($this->passwords->hash($password));
        $this->em->persist($u);
        $this->em->flush();
        return $u;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }
}
