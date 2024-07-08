<?php declare(strict_types=1);

namespace Dormilich\RPSL\Transformer;

use DateTimeInterface;
use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;
use Dormilich\RPSL\ObjectInterface;
use Stringable;

use function is_scalar;
use function is_string;
use function json_encode;

use const JSON_PRESERVE_ZERO_FRACTION;

class DefaultTransformer implements TransformerInterface
{
    private Attribute $attribute;

    /**
     * @param Attribute $attribute
     */
    public function __construct(Attribute $attribute)
    {
        $this->setAttribute($attribute);
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(Attribute $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize(mixed $value): Value
    {
        if ($value instanceof ObjectInterface) {
            return new Value($this->attribute, (string) $value->getHandle(), null, $value->getType());
        } elseif ($value instanceof Value) {
            return new Value($this->attribute, $value->getValue(), $value->getComment(), $value->getType());
        } elseif (null !== $string = $this->stringify($value)) {
            return new Value($this->attribute, $string);
        }

        throw TransformerException::forInvalidType($value, $this->attribute);
    }

    /**
     * @inheritDoc
     */
    public function unserialize(Value $value): ?string
    {
        return $value->isDefined() ? $value->getValue() : null;
    }

    /**
     * Convert input format to string, if possible.
     *
     * @param mixed $value
     * @return string|null
     */
    protected function stringify(mixed $value): ?string
    {
        if (null === $value) {
            return '';
        } elseif (is_string($value)) {
            return $value;
        } elseif (is_scalar($value)) {
            return json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
        } elseif ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::W3C);
        } elseif ($value instanceof Stringable) {
            return (string) $value;
        } else {
            return null;
        }
    }
}
