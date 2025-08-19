<?php declare(strict_types=1);

namespace Dormilich\RPSL;

use ArrayAccess;
use Countable;
use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Container;
use Dormilich\RPSL\Attribute\Presence;
use Dormilich\RPSL\Attribute\Repeat;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\AttributeException;
use Dormilich\RPSL\Exception\ConfigurationException;
use Dormilich\RPSL\Exception\TransformerException;
use IteratorAggregate;
use IteratorIterator;
use RecursiveIteratorIterator;
use Stringable;
use Traversable;

use function iterator_to_array;
use function sprintf;

/**
 * Base class providing functionality to all RPSL objects.
 */
abstract class Entity implements ArrayAccess, Countable, IteratorAggregate, ObjectInterface, Stringable
{
    private string $type;

    private Container $attributes;

    /**
     * @param string|null $handle
     * @throws ConfigurationException
     */
    public function __construct(?string $handle = null)
    {
        $this->attributes = new Container();
        $this->configure();
        $this->setType($this->attributes->first());
        $this->setHandle($handle);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string) $this->getHandle();
    }

    /**
     * Define the attributes in the RPSL object. By convention, the first attribute
     * is the type attribute of the object.
     *
     * @return void
     */
    abstract protected function configure(): void;

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the object type based on the object’s type attribute.
     *
     * @param Attribute $attribute
     * @return void
     * @throws ConfigurationException
     */
    protected function setType(Attribute $attribute): void
    {
        if ($attribute->isMultiple()) {
            $message = 'The type attribute for the "%s" RPSL object must not be multiple.';
            throw new ConfigurationException(sprintf($message, $attribute->getName()));
        }

        $this->type = $attribute->getName();
    }

    /**
     * @inheritDoc
     */
    public function getHandle(): ?string
    {
        $key = $this->getPrimaryKey();

        if ($key->any(fn(Attribute $a) => $a->isEmpty())) {
            return null;
        }

        return $key->reduce('', fn(string $handle, Attribute $a) => $handle . $a->getValue());
    }

    /**
     * Set the value of the primary key attribute. Overwrite this method for
     * composite primary keys.
     *
     * @param string|null $handle
     * @return void
     * @throws AttributeException
     * @throws TransformerException
     */
    protected function setHandle(?string $handle): void
    {
        $this->getPrimaryKey()->first()?->setValue($handle);
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return $this->attributes->has($name);
    }

    /**
     * Add an attribute definition to the RPSL object.
     *
     * @param string $name
     * @param Presence $presence
     * @param Repeat $repeat
     * @return Attribute
     */
    protected function create(string $name, Presence $presence, Repeat $repeat): Attribute
    {
        $attribute = new Attribute($name, $presence, $repeat);
        $this->attributes->add($attribute);

        return $attribute;
    }

    /**
     * @inheritDoc
     */
    public function attr(string $name): Attribute
    {
        if ($attribute = $this->attributes->get($name)) {
            return $attribute;
        }

        throw AttributeException::notFound($name, $this);
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): mixed
    {
        return $this->attr($name)->getValue();
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, mixed $value): static
    {
        $this->attr($name)->setValue($value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function add(string $name, mixed $value): static
    {
        $this->attr($name)->addValues($value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return $this->attributes
            ->retain(fn(Attribute $a) => $a->isMandatory())
            ->all(fn(Attribute $a) => $a->isDefined())
        ;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        $defined = $this->attributes->retain(fn(Attribute $a) => $a->isDefined());

        return iterator_to_array($defined, false);
    }

    /**
     * @inheritDoc
     */
    public function getValues(): array
    {
        return iterator_to_array($this->getRecursiveIterator(), false);
    }

    /**
     * Iterates over all values.
     *
     * @return Traversable<string, Value>
     */
    public function getRecursiveIterator(): Traversable
    {
        return new RecursiveIteratorIterator($this->attributes);
    }

    /**
     * Iterates over all attributes.
     *
     * @return Traversable<string, Attribute>
     */
    public function getIterator(): Traversable
    {
        return new IteratorIterator($this->attributes);
    }

    /**
     * Returns the number of non-empty attributes in the RPSL object.
     *
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->attributes->reject(fn(Attribute $a) => $a->isEmpty())->count();
    }

    /**
     * Test if the attribute exists.
     *
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Returns the attribute object.
     *
     * @param string|int $offset
     * @return Attribute
     * @throws AttributeException
     */
    public function offsetGet(mixed $offset): Attribute
    {
        return $this->attr($offset);
    }

    /**
     * Re-sets the attribute value.
     *
     * @param string|int|null $offset
     * @param mixed $value
     * @return void
     * @throws AttributeException
     * @throws TransformerException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Unsets the attribute value.
     *
     * @param string|int $offset
     * @return void
     * @throws AttributeException
     * @throws TransformerException
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->attributes->get($offset)?->setValue(null);
    }

    /**
     * Returns the primary key attributes.
     *
     * @return Container
     */
    protected function getPrimaryKey(): Container
    {
        return $this->attributes->retain(fn(Attribute $a) => $a->isPrimaryKey());
    }
}
