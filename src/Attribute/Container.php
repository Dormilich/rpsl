<?php declare(strict_types=1);

namespace Dormilich\RPSL\Attribute;

use Closure;
use Countable;
use Iterator;
use RecursiveIterator;

use function array_key_exists;
use function array_map;
use function array_reduce;
use function current;
use function key;
use function next;
use function reset;

/**
 * @internal
 * @implements Iterator<string, Attribute>
 */
class Container implements Countable, RecursiveIterator
{
    /**
     * @var Attribute[]
     */
    private array $attributes = [];

    /**
     * @param Attribute $attribute
     * @return void
     */
    public function add(Attribute $attribute): void
    {
        $this->attributes[$attribute->getName()] = $attribute;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @param string $name
     * @return Attribute|null
     */
    public function get(string $name): ?Attribute
    {
        if ($this->has($name)) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * @return Attribute|null
     */
    public function first(): ?Attribute
    {
        return reset($this->attributes) ?: null;
    }

    /**
     * Keep all attributes that satisfy the condition.
     *
     * @param Closure $fn
     * @return self
     */
    public function retain(Closure $fn): self
    {
        $result = new Container();

        foreach ($this->attributes as $attribute) {
            if ($fn($attribute)) {
                $result->add($attribute);
            }
        }

        $result->rewind();

        return $result;
    }

    /**
     * Discard all attributes that satisfy the condition.
     *
     * @param Closure $fn
     * @return self
     */
    public function reject(Closure $fn): self
    {
        $result = new Container();

        foreach ($this->attributes as $attribute) {
            if (!$fn($attribute)) {
                $result->add($attribute);
            }
        }

        $result->rewind();

        return $result;
    }

    /**
     * Map each attribute to a value.
     *
     * @param Closure $fn
     * @return iterable
     */
    public function map(Closure $fn): iterable
    {
        return array_map($fn, $this->attributes);
    }

    /**
     * Reduce all attributes to a single value.
     *
     * @param Closure $fn
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(mixed $initial, Closure $fn): mixed
    {
        return array_reduce($this->attributes, $fn, $initial);
    }

    /**
     * Returns TRUE only if all attributes satisfy the condition.
     *
     * @param Closure $fn
     * @return bool
     */
    public function all(Closure $fn): bool
    {
        foreach ($this->attributes as $attribute) {
            if (!$fn($attribute)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns TRUE if at least one attribute satisfies the condition.
     *
     * @param Closure $fn
     * @return bool
     */
    public function any(Closure $fn): bool
    {
        foreach ($this->attributes as $attribute) {
            if ($fn($attribute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return null !== $this->key();
    }

    /**
     * @inheritDoc
     */
    public function current(): ?Attribute
    {
        return current($this->attributes) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function key(): string|null
    {
        return key($this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        next($this->attributes);
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(): bool
    {
        // `Attribute` is not a leaf node
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): ?RecursiveIterator
    {
        return $this->current();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->attributes);
    }
}
