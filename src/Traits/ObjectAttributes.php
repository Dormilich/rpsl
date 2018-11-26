<?php
// ObjectAttributes.php

namespace Dormilich\RPSL\Traits;

use Dormilich\RPSL\AttributeInterface;
use Dormilich\RPSL\Exceptions\InvalidAttributeException;

/**
 * @uses ObjectName
 */
trait ObjectAttributes
{
    /**
     * @var AttributeInterface[]  Name-indexed array of attributes.
     */
    private $attributes = [];

    /**
     * @var AttributeInterface[]  Name-indexed array of auto-generated attributes.
     */
    private $generated = [];

    /**
     * Get the attributes, optionally including the generated ones.
     * 
     * @param boolean|false $include_generated Whether to return generated 
     *          attributes as well.
     * @return AttributeInterface[]
     */
    protected function getAttributes( $include_generated = false )
    {
        $list = $this->attributes;

        if ( $include_generated ) {
            $list += $this->generated;
        }

        return $list;
    }

    /**
     * Add an attribute object to the attributes list. 
     * 
     * @param AttributeInterface $attribute An attribute object.
     * @return AttributeInterface
     */
    protected function setAttribute( AttributeInterface $attribute )
    {
        return $this->attributes[ $attribute->getName() ] = $attribute;
    }

    /**
     * Add an attribute object to the generated attributes list.
     * 
     * @param AttributeInterface $attribute 
     * @return AttributeInterface
     */
    protected function setGenerated( AttributeInterface $attribute )
    {
        return $this->generated[ $attribute->getName() ] = $attribute;
    }

    /**
     * Check if a specific attribute exists in this object.
     * 
     * @param string $name Name of the attribute.
     * @return boolean Whether the attribute exists
     */
    public function has( $name )
    {
        return isset( $this->attributes[ $name ] ) 
            or isset( $this->generated[ $name ] ); 
    }

    /**
     * Get an attribute’s value(s).
     * 
     * @param string $name Attribute name.
     * @return string[]|string|NULL Attribute value(s).
     */
    public function get( $name )
    {
        return $this->attr( $name )->getValue();
    }

    /**
     * Set an attribute’s value(s).
     * 
     * @param string $name Attribute name.
     * @param mixed $value Attibute value(s).
     * @return self
     */
    public function set( $name, $value )
    {
        $this->attr( $name )->setValue( $value );

        return $this;
    }

    /**
     * Add a value to an attribute.
     * 
     * @param string $name Attribute name.
     * @param mixed $value Attibute value(s).
     * @return self
     */
    public function add( $name, $value )
    {
        $this->attr( $name )->addValue( $value );

        return $this;
    }

    /**
     * Get an attribute specified by name.
     * 
     * @param string $name Name of the attribute.
     * @return AttributeInterface Attribute object.
     * @throws InvalidAttributeException Invalid argument name.
     */
    public function attr( $name )
    {
        if ( isset( $this->attributes[ $name ] ) ) {
            return $this->attributes[ $name ];
        }

        if ( isset( $this->generated[ $name ] ) ) {
            return $this->generated[ $name ];
        }

        $msg = sprintf( 'Attribute "%s" is not defined for the %s object.', 
            $name, strtoupper( $this->getName() ) );
        throw new InvalidAttributeException( $msg );
    }
}
