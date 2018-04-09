<?php
// ObjectKey.php

namespace Dormilich\RPSL\Traits;

use Dormilich\RPSL\AttributeInterface;

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

        foreach ( $keys as $key => $value) {
            $this->set( $key, $value );
        }
    }

    /**
     * Get the value of the attribute(s) defined as primary key. Returns NULL if 
     * any of the primary key attributes (=> composite PK) is not set.
     * 
     * @return string|NULL
     */
    public function getHandle()
    {
        $primary_attr = array_map( function ( $key ) {
            return $this->attr( $key );
        }, $this->primaryKey );

        // prevent an incomplete composite primary key value
        $defined = array_reduce( $primary_attr, function ( $bool, AttributeInterface $attr ) {
            return $bool and $attr->isDefined();
        }, true );

        if ( ! $defined ) {
            return NULL;
        }
        // omit comments on primary key attributes
        return array_reduce( $primary_attr, function ( $value, \Iterator $attr ) {
            $attr->rewind();
            return $value . $attr->current()->value();
        }, '' );
    }
}
