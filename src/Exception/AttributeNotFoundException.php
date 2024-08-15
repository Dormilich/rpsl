<?php declare(strict_types=1);

namespace Dormilich\RPSL\Exception;

use Dormilich\RPSL\ObjectInterface;
use OutOfBoundsException;

class AttributeNotFoundException extends OutOfBoundsException implements RPSLException
{
    /**
     * @param string $name
     * @param ObjectInterface $object
     * @return AttributeNotFoundException
     */
    public static function for(string $name, ObjectInterface $object): AttributeNotFoundException
    {
        $message = sprintf('Attribute "%s" does not exist in the [%s] object.', $name, $object->getType());

        return new AttributeNotFoundException($message);
    }
}
