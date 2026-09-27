<?php declare(strict_types=1);

namespace App\Utils;

/**
 * Jednoduché magic accessors (hlavně pro čtení v Latte šablonách):
 *  $obj->foo         => $obj->getFoo()
 *  $obj->foo = $val  => $obj->setFoo($val)  (jen pro „virtuální“ vlastnosti, viz __set)
 *  isset($obj->foo)  => getFoo() !== null / isFoo() === true
 */
trait MagicAccessors
{
    public function __get(string $name)
    {
        $g = 'get' . ucfirst($name);
        if (method_exists($this, $g)) {
            return $this->$g();
        }
        $i = 'is' . ucfirst($name);
        if (method_exists($this, $i)) {
            return $this->$i();
        }
        throw new \LogicException("Property '$name' not readable on " . static::class);
    }

    public function __set(string $name, $value): void
    {
        // Deklarovaná vlastnost: zapisuje ji Doctrine při inicializaci lazy proxy (vlastnosti jsou
        // do té doby unset, proto zápis skončí tady). Musí jít přímo – ne přes setter, který nemusí
        // existovat (read-only entity) nebo může mít vedlejší efekty/typová omezení.
        // self::class = entita, která trait používá (proxy je její podtřída a privátní vlastnosti rodiče nevidí)
        if (property_exists(self::class, $name)) {
            $this->$name = $value;
            return;
        }

        $s = 'set' . ucfirst($name);
        if (method_exists($this, $s)) {
            $this->$s($value);
            return;
        }
        throw new \LogicException("Property '$name' not writable on " . static::class);
    }

    public function __isset(string $name): bool
    {
        $g = 'get' . ucfirst($name);
        if (method_exists($this, $g)) {
            return $this->$g() !== null;
        }
        $i = 'is' . ucfirst($name);
        if (method_exists($this, $i)) {
            return (bool) $this->$i();
        }
        return false;
    }
}
