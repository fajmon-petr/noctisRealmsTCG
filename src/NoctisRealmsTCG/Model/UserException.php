<?php declare(strict_types=1);

namespace App\Model;

/**
 * Chyba, kterou lze ukázat hráči (zpráva je česky) – např. nedostatek Moon Dustu.
 * Fasády ji vyhazují jen před změnou dat, takže po ní není co uklízet.
 */
class UserException extends \RuntimeException
{
}
