<?php declare(strict_types=1);

namespace App\Model\Database;

use App\Model\UserException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transakce bez zavírání EntityManageru.
 *
 * `EntityManager::wrapInTransaction()` při jakékoli výjimce EntityManager zavře – presenter by pak
 * nemohl ani vykreslit chybovou hlášku. Tady se při UserException (kontroly před změnou dat)
 * jen vrátí transakce; při neočekávané chybě se navíc zahodí rozpracované změny v paměti.
 *
 * @property-read EntityManagerInterface $em
 */
trait ManualTransaction
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function transactional(callable $callback): mixed
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();
        try {
            $result = $callback();
            $this->em->flush();
            $connection->commit();
            return $result;
        } catch (UserException $e) {
            $connection->rollBack(); // kontroly proběhly před změnami dat → nic k zahození
            throw $e;
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->em->clear(); // neočekávaná chyba – rozpracované změny v paměti zahodit
            throw $e;
        }
    }
}
