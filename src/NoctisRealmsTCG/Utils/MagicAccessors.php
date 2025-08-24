<?php declare(strict_types=1);

namespace App\Utils;

/**
 * Jednoduché magic accessors:
 *  $obj->foo         => $obj->getFoo()
 *  $obj->foo = $val  => $obj->setFoo($val)
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
