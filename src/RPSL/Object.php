<?php
// Object.php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Exceptions\IncompleteObjectException;
use Dormilich\RPSL\Exceptions\InvalidAttributeException;
use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;

/**
 * The prototype for every RPSL object class. 
 * 
 * A child class must
 *  1) set the class name to the type’s name using CamelCase (inet6num => Inet6num, aut-num => AutNum)
 *  2) configure the attributes for the RPSL object
 *  3) if the primary key is not the same as the type, implement an appropriate constructor
 * 
 * A child class should
 *  - set the primary key on instantiation
 *  - set a "VERSION" constant
 */
abstract class Object implements ObjectInterface, NamespaceAware, \ArrayAccess, \Iterator, \Countable, \JsonSerializable
{
    use Traits\ObjectAttributes
      , Traits\ObjectName
      , Traits\ObjectKey
    ;

    /**
     * Create an RPSL object. This is intended for objects that have a single 
     * primary key. Objects with composite primary keys (Route) or cases where 
     * the primary key is a different from the object type (Role) need to 
     * implement their own constructor.
     * 
     * @param string $value The primary key value for this object.
     * @return self
     */
    public function __construct( $value )
    {
        $this->init();
        $this->setName();
        $this->setKey( [
            $this->getName() => $value,
        ] );
    }

    /**
     * Define the attributes for this object according to the respective docs.
     * 
     * @return void
     */
    abstract protected function init();

    /**
     * Create an attribute and add it to the attribute list. If a public 
     * method of a matching name exists, it is registered as callback.
     * 
     * @param string $name Name of the attribute.
     * @param boolean $required If the attribute is mandatory.
     * @param boolean $multiple If the attribute allows multiple values.
     * @return AttributeInterface
     */
    protected function define( $name, $required, $multiple )
    {
        $attr = new Attribute( $name, $required, $multiple, $this );

        return $this->setAttribute( $attr );
    }

    /**
     * Set a generated attribute. These attributes are not serialised. Its values 
     * are only accessible from the object itself. Generated attributes are always 
     * optional.
     * 
     * @param string $name Name of the attribute.
     * @param boolean $multiple If the attribute allows multiple values.
     * @return AttributeInterface
     */
    protected function generated( $name, $multiple )
    {
        $attr = new Attribute( $name, AttributeInterface::OPTIONAL, $multiple, $this );

        return $this->setGenerated( $attr );
    }

// --- DATA ACCESS ----------------

    /**
     * Output the object’s handle when used as string.
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getHandle();
    }

    /**
     * Convert the object as a textual list of its defined attributes. 
     * Requires the Attribute implementation to be iterable.
     * 
     * @return string
     */
    public function toText()
    {
        $max = 3 + max( array_map( 'strlen', $this->getAttributeNames() ) );

        $text = '';
        // can’t rely on the AttributeValue object being used
        foreach ( $this as $name => $attr ) {
            foreach ( $attr as $value ) {
                $text .= sprintf( "%-{$max}s %s\n", $name . ':', $value );
            }
        }
        return $text;
    }

    /**
     * Convert object into an array, where all objects are converted into their 
     * array equivalent.
     * 
     * @return array
     */
    public function toArray()
    {
        $json  = json_encode( $this->jsonAttributes() );
        $array = json_decode( $json, true );

        return $array;
    }

    /**
     * Get the array representation of all attributes that are populated with 
     * values. Generated attributes are ignored since they are always generated 
     * by the RPSL DB.
     * 
     * @return array JSON compatible array.
     */
    protected function jsonAttributes()
    {
        $json = array_map( function ( JsonSerializable $attr ) {
            return $attr->jsonSerialize();
        }, $this->getDefinedAttributes() );

        return call_user_func_array( 'array_merge', $json );
    }

    /**
     * Get the keys for the attributes (no matter whether they’re defined or not).
     * 
     * @return string[]
     */
    public function getAttributeNames()
    {
        return array_keys( $this->getAttributes( true ) );
    }

    /**
     * Filter all attributes that are defined.
     * 
     * @return array
     */
    protected function getDefinedAttributes()
    {
        return array_filter( $this->getAttributes(), function ( AttributeInterface $attr ) {
            return $attr->isDefined();
        });
    }

// --- VALIDATION -----------------

    /**
     * Check if any of the required Attributes is undefined.
     * 
     * @return boolean
     */
    public function isValid()
    {
        return array_reduce( $this->getAttributes(), function ( $bool, AttributeInterface $attr ) {
            return $bool and ( ! $attr->isRequired() or $attr->isDefined() );
        }, true );
    }

    /**
     * Throw an exception when a required attribute is not defined.
     * 
     * @return boolean
     * @throws IncompleteObjectException
     */
    public function validate()
    {
        foreach ( $this->getAttributes() as $attr ) {
            if ( $attr->isRequired() and ! $attr->isDefined() ) {
                $msg = sprintf( 'Mandatory attribute "%s" is not set.', $attr->getName() );
                throw new IncompleteObjectException( $msg );
            }
        }

        return true;
    }

// --- PHP INTERFACES -------------

    /**
     * Serializes the object to a structure that can be serialized natively by 
     * json_encode(). The Attribute implementation must ensure that the required 
     * JSON structure is returned.
     * 
     * @see http://php.net/JsonSerializable
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->jsonAttributes();
    }

    /**
     * Checks if an Attribute exists, but not if it is populated.
     * 
     * @see http://php.net/ArrayAccess
     * @param mixed $offset The array key.
     * @return boolean
     */
    public function offsetExists( $offset )
    {
        return $this->has( $offset );
    }

    /**
     * Get the object for the specified Attribute. This allows to iterate over 
     * the attribute values and access attribute (value) properties.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute name.
     * @return AttributeInterface The attribute object.
     */
    public function offsetGet( $offset )
    {
        if ( $this->has( $offset ) ) {
            return $this->attr( $offset );
        }

        return NULL;
    }

    /**
     * Set an Attibute’s value. Existing values will be replaced. 
     * For adding values use Object::add().
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute name.
     * @param mixed $value New Attribute value.
     * @return void
     */
    public function offsetSet( $offset, $value )
    {
        if ( $this->has( $offset ) ) {
            $this->attr( $offset )->setValue( $value );
        }
    }

    /**
     * Reset an Attribute’s value.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute name.
     * @return void
     */
    public function offsetUnset( $offset )
    {
        if ( $this->has( $offset ) ) {
            $this->attr( $offset )->setValue( NULL );
        }
    }

    /**
     * Return the number of defined Attributes.
     * 
     * @see http://php.net/Countable
     * @return integer
     */
    public function count()
    {
        return count( $this->getDefinedAttributes() );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function rewind()
    {
        reset( $this->attributes );
    }
    
    /**
     * @see http://php.net/Iterator
     * @return AttributeInterface
     */
    public function current()
    {
        return current( $this->attributes );
    }
    
    /**
     * @see http://php.net/Iterator
     * @return integer
     */
    public function key()
    {
        return key( $this->attributes );
    }
    
    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function next()
    {
        next( $this->attributes );
    }
    
    /**
     * @see http://php.net/Iterator
     * @return boolean
     */
    public function valid()
    {
        return NULL !== key( $this->attributes );
    }
}
