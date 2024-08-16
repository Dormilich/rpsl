<?php declare(strict_types=1);

namespace Dormilich\RPSL\Attribute;

use ArrayAccess;
use Countable;
use Dormilich\RPSL\Exception\TransformerException;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Transformer\DefaultTransformer;
use Dormilich\RPSL\Transformer\TransformerInterface;
use RecursiveIterator;
use Traversable;

use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function current;
use function explode;
use function is_array;
use function is_string;
use function iterator_to_array;
use function key;
use function next;
use function reset;
use function str_replace;

class Attribute implements ArrayAccess, Countable, RecursiveIterator
{
    /**
     * @var Value[]
     */
    private array $values = [];

    /**
     * @var TransformerInterface Used for data format conversion.
     */
    private TransformerInterface $transformer;

    /**
     * @param string $name
     * @param Presence $presence Whether the attribute is mandatory in the parent object.
     * @param Repeat $repeat Whether the attribute is allowed a single value at most.
     */
    public function __construct(
        private readonly string $name,
        private readonly Presence $presence,
        private readonly Repeat $repeat
    ) {
        $this->apply(new DefaultTransformer());
    }

    /**
     * Set the data transformer for this attribute.
     *
     * @param TransformerInterface $transformer
     * @return static
     */
    public function apply(TransformerInterface $transformer): static
    {
        $this->transformer = $transformer->setAttribute($this);

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the attribute value(s) depending on the multiplicity of the attribute.
     * This will ignore inline comments. If comments should be displayed, use
     * iteration instead.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        $values = $this->getValues();

        if ($this->isEmpty()) {
            return null;
        } elseif ($this->isSingle()) {
            return reset($values);
        } else {
            return $values;
        }
    }

    /**
     * Re-set the attribute values.
     *
     * @param mixed $value
     * @return self
     */
    public function setValue(mixed $value): self
    {
        $this->values = [];

        if (null !== $value) {
            $this->addValues($value);
        }

        return $this;
    }

    /**
     * Add one or more values to the attribute.
     *
     * @param mixed $value
     * @return self
     * @throws TransformerException
     */
    public function addValues(mixed $value): self
    {
        $values = $this->toIterable($value);
        $values = $this->transformValues($values);
        $values = $this->combineValues($values);

        if ($this->isMultiple() or count($values) <= 1) {
            $this->values = $values;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(): bool
    {
        return Presence::primary_key === $this->presence;
    }

    /**
     * @return bool
     */
    public function isGenerated(): bool
    {
        return Presence::generated === $this->presence;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return Presence::mandatory === $this->presence or $this->isPrimaryKey();
    }

    /**
     * @return bool
     */
    public function isOptional(): bool
    {
        return Presence::optional === $this->presence or $this->isGenerated();
    }

    /**
     * @return bool
     */
    public function isSingle(): bool
    {
        return Repeat::single === $this->repeat;
    }

    /**
     * @return bool
     */
    public function isMultiple(): bool
    {
        return Repeat::multiple === $this->repeat;
    }

    /**
     * Returns TRUE if at least one value is defined. I.e. if an attribute only
     * contains empty values, it is considered empty as well.
     *
     * @return bool
     */
    public function isDefined(): bool
    {
        foreach ($this->values as $value) {
            if ($value->isDefined()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !$this->isDefined();
    }

    /**
     * Convert internal values to strings.
     *
     * @return array<int, mixed>
     */
    private function getValues(): array
    {
        return array_map(fn(Value $value) => $this->transformer->unserialize($value), $this->values);
    }

    /**
     * Convert input into a list of values.
     *
     * @param mixed $value
     * @return array
     */
    private function toIterable(mixed $value): array
    {
        if (is_string($value)) {
            return $this->splitBlockText($value);
        } elseif (is_array($value)) {
            return $value;
        } elseif ($value instanceof ObjectInterface) {
            return [$value];
        } elseif ($value instanceof Traversable) {
            return iterator_to_array($value, false);
        } else {
            return [$value];
        }
    }

    /**
     * Split text into separate lines.
     *
     * @param string $text
     * @return string[]
     */
    private function splitBlockText(string $text): array
    {
        return explode("\n", str_replace("\r\n", "\n", $text));
    }

    /**
     * Apply value transformation to the input.
     *
     * @param array $items
     * @return Value[]
     * @throws TransformerException
     */
    private function transformValues(array $items): array
    {
        return array_map(fn($item) => $this->transformer->serialize($item), $items);
    }

    /**
     * Merge attribute values together and re-index the result array.
     *
     * @param Value[] $items
     * @return Value[]
     */
    private function combineValues(array $items): array
    {
        return array_merge($this->values, $items);
    }

    ### Countable ###

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->values);
    }

    ### Iterator ###

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->values);
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
    public function current(): ?Value
    {
        return current($this->values) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function key(): ?int
    {
        return key($this->values);
    }

    /**
     * @inheritDoc
     */
    public function next(): void
    {
        next($this->values);
    }

    ### RecursiveIterator ###

    /**
     * @inheritDoc
     */
    public function hasChildren(): bool
    {
        // `Value` is a leaf node
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): ?RecursiveIterator
    {
        return null;
    }

    ### ArrayAccess ###

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->values);
    }

    /**
     * @inheritDoc
     * @return string|null
     */
    public function offsetGet(mixed $offset): ?string
    {
        if ($this->offsetExists($offset)) {
            return $this->values[$offset]->getValue();
        }

        return null;
    }

    /**
     * Allow appending values via `$attribute[] = $value`.
     *
     * @param string|int|null $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null === $offset) {
            $this->addValues($value);
        }
    }

    /**
     * Do not remove values.
     *
     * @param string|int $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        # ignore
    }
}
