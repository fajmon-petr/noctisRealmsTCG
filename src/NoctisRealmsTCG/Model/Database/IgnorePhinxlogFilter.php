<?php declare(strict_types=1);

namespace App\Model\Database;

use Doctrine\DBAL\Schema\AbstractAsset;

/** Doctrine schema tool ignoruje tabulku `phinxlog` (patří Phinx migracím, nemá entitu) */
final class IgnorePhinxlogFilter
{
    public function __invoke(string|AbstractAsset $asset): bool
    {
        $name = $asset instanceof AbstractAsset ? $asset->getName() : $asset;
        return $name !== 'phinxlog';
    }
}
