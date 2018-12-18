<?php
// ObjectKey.php

namespace Dormilich\RPSL\Traits;

use Dormilich\RPSL\AttributeInterface;
use Dormilich\RPSL\AttributeValue;

/**
 * @uses ObjectAttributes
 */
trait ObjectKey
{
    /**
     * @var string[] The primary lookup key name of the object.
     */
    private $primaryKey;

    /**
     * @see ObjectAttributes
     */
    abstract protected function getAttributes( $include_generated = false );

    /**
     * @see ObjectAttributes
     */
    abstract public function set( $name, $value );

    /**
     * @see ObjectName
     */
    abstract public function getName();

    /**
     * Useful helper in form templates. This needs to be an array since Route / 
     * Route6 have a composite primary key. 
     * 
     * @return string[]
     */
    public function getPrimaryKeyNames()
    {
        return $this->primaryKey;
    }

    /**
     * Set name(s) and value(s) of the (composite) primary key. 
     * 
     * Note: It's the responsibility of the coder to make sure all primary key 
     *       attributes are single and required!
     * 
     * @param array $keys Key name vs. key value.
     * @return void
     */
    protected function setKey( array $keys )
    {
        if ( count( $keys ) === 0 ) {
            return;
        }

        $this->primaryKey = array_keys( $keys );

        foreach ( $keys as $key => $value ) {
            $this->set( $key, $value );
        }
    }

    /**
     * Convert constructor argument into a key name/key value list.
     * 
     * @param string $value 
     * @return array
     */
    protected function keysFromInput( $value )
    {
        return [ $this->getName() => $value ];
    }

    /**
     * Get the value of the attribute(s) defined as primary key. Returns NULL if 
     * any of the primary key attributes (=> composite PK) is not set.
     * 
     * @return string|NULL
     */
    public function getHandle()
    {
        $primary = $this->getPrimaryAttributes();
        // prevent an incomplete composite primary key value
        if ( ! $this->isHandleDefined( $primary ) ) {
            return NULL;
        }
        // omit comments on primary key attributes
        $values = $this->getPrimaryValues( $primary );

        return implode( '', $values );
    }

    /**
     * Get the attribute objects for the primary key.
     * 
     * @return AttributeInterface[]
     */
    private function getPrimaryAttributes()
    {
        $attr = $this->getAttributes();
        $keys = array_flip( $this->primaryKey );

        return array_intersect_key( $attr, $keys );
    }

    /**
     * Get the attribute values for the primary key.
     * 
     * @param AttributeInterface[] $attributes 
     * @return string[]
     */
    private function getPrimaryValues( array $attributes )
    {
        // depends on `jsonSerialize()` returning the value objects
        return array_map( function ( \JsonSerializable $attr ) {
            return $attr->jsonSerialize()[ 0 ]->value();
        }, $attributes );
    }

    /**
     * Test if the (composite) primary key is correctly defined.
     * 
     * @param array $attributes 
     * @return boolean
     */
    private function isHandleDefined( array $attributes )
    {
        // it makes no sense for a primary key component to be multi-valued
        return array_reduce( $attributes, function ( $bool, AttributeInterface $attr ) {
            return $bool and $attr->isDefined() and $attr->isSingle();
        }, true );
    }
}
