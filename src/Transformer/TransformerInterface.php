<?php

namespace Dormilich\RPSL\Transformer;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;

interface TransformerInterface
{
    /**
     * Convert data from the input format into the internal format.
     *
     * @param mixed $value
     * @return Value
     * @throws TransformerException
     */
    public function serialize(mixed $value): Value;

    /**
     * Convert the internal data into a convenient data format for handling in PHP.
     *
     * @param Value $value
     * @return mixed
     * @throws TransformerException
     */
    public function unserialize(Value $value): mixed;

    /**
     * Set te attribute used to create the value objects.
     *
     * @param Attribute $attribute
     * @return static
     */
    public function setAttribute(Attribute $attribute): static;
}
