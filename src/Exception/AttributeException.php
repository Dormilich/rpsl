<?php

namespace Dormilich\RPSL\Exception;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\ObjectInterface;
use Exception;

use function sprintf;

class AttributeException extends Exception implements RPSLException
{
    public const ATTRIBUTE_NOT_FOUND = 1031;

    public const ARGUMENT_PLURALITY_SINGLE = 1033;

    /**
     * Create exception when an attribute is not defined.
     *
     * @param string $name
     * @param ObjectInterface $object
     * @return static
     */
    public static function notFound(string $name, ObjectInterface $object): static
    {
        $message = sprintf('Attribute "%s" does not exist in the [%s] object.', $name, $object->getType());

        return new static($message, self::ATTRIBUTE_NOT_FOUND);
    }

    /**
     * Create exception when multiple values are added to a single attribute.
     *
     * @param Attribute $attribute
     * @return static
     */
    public static function singlePlurality(Attribute $attribute): static
    {
        $message = sprintf('Cannot add multiple values to single attribute "%s".', $attribute->getName());

        return new static($message, self::ARGUMENT_PLURALITY_SINGLE);
    }
}
