<?php
// ObjectInterface.php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Exceptions\IncompleteObjectException;

interface ObjectInterface
{
    /**
     * Get the name/type of the current RPSL object.
     * 
     * @return string RPSL object name.
     */
    public function getName();

    /**
     * Get the value of the attribute(s) defined as primary key.
     * 
     * @return string
     */
    public function getHandle();

    /**
     * Check if a specific attribute exists in this object.
     * 
     * @param string $name Name of the attribute.
     * @return boolean Whether the attribute exists
     */
    public function has( $name );

    /**
     * Get an attribute object specified by name.
     * 
     * @param string $name Name of the attribute.
     * @return AttributeInterface Attribute object.
     * @throws InvalidAttributeException Invalid argument name.
     */
    public function attr( $name );

    /**
     * Get an attribute’s value(s). This may throw an exception if the attribute 
     * does not exist.
     * 
     * @param string $name Attribute name.
     * @return string[]|string|NULL Attribute value(s).
     */
    public function get( $name );

    /**
     * Set an attribute’s value(s). This may throw an exception if multiple 
     * values are not supported by the underlying attribute.
     * 
     * @param string $name Attribute name.
     * @param mixed $value Attibute value(s).
     * @return self
     */
    public function set( $name, $value );

    /**
     * Add value(s) to an attribute. This may throw an exception if multiple 
     * values are not supported by the underlying attribute.
     * 
     * @param string $name Attribute name.
     * @param mixed $value Attibute value(s).
     * @return self
     */
    public function add( $name, $value );

    /**
     * Check if any of the required Attributes is undefined.
     * 
     * @return boolean
     */
    public function isValid();

    /**
     * Throw an exception when a required attribute is not defined.
     * 
     * @return void
     * @throws IncompleteObjectException
     */
    public function validate();
}
