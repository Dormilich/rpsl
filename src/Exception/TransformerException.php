<?php declare(strict_types=1);

namespace Dormilich\RPSL\Exception;

use Dormilich\RPSL\Attribute\Attribute;
use Exception;
use Throwable;

use function get_debug_type;
use function json_encode;

/**
 * Thrown when data conversion fails.
 */
class TransformerException extends Exception implements RPSLException
{
    public const INVALID_DATA_TYPE = 4001;
    public const INVALID_VALUE = 4003;

    /**
     * Create exception for a value with an inappropriate data type.
     *
     * @param mixed $value
     * @param Attribute $attribute
     * @param Throwable|null $previous
     * @return static
     */
    public static function forInvalidType(mixed $value, Attribute $attribute, Throwable $previous = null): static
    {
        $message = sprintf('Value of type [%s] could not be stored in the "%s" attribute.',
            get_debug_type($value), $attribute->getName());

        return new static($message, TransformerException::INVALID_DATA_TYPE, $previous);
    }

    /**
     * Create exception for an invalid value (even though the data type is acceptable).
     *
     * @param scalar $value
     * @param Attribute $attribute
     * @param Throwable|null $previous
     * @return static
     */
    public static function forInvalidValue(mixed $value, Attribute $attribute, Throwable $previous = null): static
    {
        $message = sprintf('Value [%s] could not be stored in the "%s" attribute.',
            json_encode($value), $attribute->getName());

        return new static($message, TransformerException::INVALID_VALUE, $previous);
    }
}
