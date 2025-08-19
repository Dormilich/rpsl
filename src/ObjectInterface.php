<?php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\AttributeException;
use Dormilich\RPSL\Exception\TransformerException;

interface ObjectInterface
{
    /**
     * Get the name/type of the current RPSL object.
     *
     * @return string RPSL object name.
     */
    public function getType(): string;

    /**
     * Get the value of the attribute(s) defined as primary key.
     *
     * @return string|null
     */
    public function getHandle(): ?string;

    /**
     * Check if a specific attribute exists in this object.
     *
     * @param string $name Name of the attribute.
     * @return bool Whether the attribute exists
     */
    public function has(string $name): bool;

    /**
     * Get an attribute object specified by name.
     *
     * @param string $name Name of the attribute.
     * @return Attribute
     * @throws AttributeException
     */
    public function attr(string $name): Attribute;

    /**
     * Get an attribute’s value(s). This may throw an exception if the attribute
     * does not exist.
     *
     * @param string $name Attribute name.
     * @return mixed Attribute value(s).
     * @throws AttributeException
     * @throws TransformerException
     */
    public function get(string $name): mixed;

    /**
     * Set an attribute’s value(s). This may throw an exception if multiple
     * values are not supported by the underlying attribute.
     *
     * @param string $name Attribute name.
     * @param mixed $value Attribute value(s).
     * @return static
     * @throws AttributeException
     * @throws TransformerException
     */
    public function set(string $name, mixed $value): static;

    /**
     * Add value(s) to an attribute. This may throw an exception if multiple
     * values are not supported by the underlying attribute.
     *
     * @param string $name Attribute name.
     * @param mixed $value Attribute value(s).
     * @return static
     * @throws AttributeException
     * @throws TransformerException
     */
    public function add(string $name, mixed $value): static;

    /**
     * Returns all defined attributes.
     *
     * @return array<string, Attribute>
     */
    public function getAttributes(): array;

    /**
     * Returns all values.
     *
     * @return array<string, Value>
     */
    public function getValues(): array;

    /**
     * Check if any of the required Attributes is undefined.
     *
     * @return bool
     */
    public function isValid(): bool;
}
