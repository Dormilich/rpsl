<?php declare(strict_types=1);

namespace Dormilich\RPSL\Transformer;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;
use Exception;

use function get_class;
use function is_string;

class DatetimeTransformer implements TransformerInterface
{
    /**
     * @var class-string<DateTimeInterface>
     */
    protected string $dateClass;

    /**
     * @var DateTimeZone Timezone of the output date object.
     */
    protected DateTimeZone $timezone;

    /**
     * @var Attribute
     */
    private Attribute $attribute;

    /**
     * @param DateTimeInterface|null $date
     */
    public function __construct(DateTimeInterface $date = null)
    {
        $date = $date ?: new DateTimeImmutable();

        $this->dateClass = get_class($date);
        $this->timezone = $date->getTimezone();
    }

    /**
     * @inheritDoc
     */
    public function serialize(mixed $value): Value
    {
        if (null === $value or '' === $value) {
            return new Value($this->attribute, '');
        }

        if (!$value instanceof Value) {
            $comment = null;
            $date = $value;
        } elseif ($value->isEmpty()) {
            return new Value($this->attribute, '');
        } else {
            $comment = $value->getComment();
            $date = $value->getValue();
        }

        $timestamp = $this->toDateTime($date)->format(DateTimeInterface::W3C);

        return new Value($this->attribute, $timestamp, $comment);
    }

    /**
     * @inheritDoc
     */
    public function unserialize(Value $value): DateTimeInterface|string|null
    {
        if ($value->isEmpty()) {
            return null;
        }

        if ($datetime = $this->toDateTime($value->getValue())) {
            return $this->createFrom($datetime);
        }

        return $value->getValue();
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(Attribute $attribute): static
    {
        $that = clone $this;

        $that->attribute = $attribute;

        return $that;
    }

    /**
     * @param mixed $date
     * @return DateTimeInterface
     * @throws TransformerException
     */
    protected function toDateTime(mixed $date): DateTimeInterface
    {
        if ($date instanceof DateTimeInterface) {
            return $date;
        }
        if (!is_string($date)) {
            throw TransformerException::forInvalidType($date, $this->attribute);
        }
        if ($datetime = DateTimeImmutable::createFromFormat(DateTimeInterface::W3C, $date, $this->timezone)) {
            return $datetime;
        }

        try {
            return new DateTimeImmutable($date, $this->timezone);
        } catch (Exception $e) {
            throw TransformerException::forInvalidValue($date, $this->attribute, $e);
        }
    }

    /**
     * Recreate datetime object in the target format.
     *
     * @param DateTimeInterface $datetime
     * @return DateTimeInterface
     */
    protected function createFrom(DateTimeInterface $datetime): DateTimeInterface
    {
        $class = $this->dateClass;
        $object = new $class($datetime->format(DateTimeInterface::W3C));

        return $object->setTimezone($this->timezone);
    }
}
