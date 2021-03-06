<?php
// AbstractObject.php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Exceptions\IncompleteObjectException;
use Dormilich\RPSL\Exceptions\InvalidAttributeException;
use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;

/**
 * Define base functionality for RPSL objects:
 *  - object configuration
 *  - attribute access
 *  - object validation
 * 
 * Object configuration:
 *  - the class name must match the type’s name (inet6num => Inet6num, aut-num => AutNum)
 *  - if the primary key is not the same as the type, implement an appropriate converter
 *  - there should be a "VERSION" constant to keep track of configuration changes
 */
abstract class AbstractObject implements ObjectInterface, NamespaceAware, \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
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
        // auto-set name from class
        $this->setName();
        // just a safety measure ... should be re-defined in configure()
        $this->define( $this->getName(), AttributeInterface::MANDATORY, AttributeInterface::SINGLE );
        // define attributes
        $this->configure();
        // set primary key attributes
        $keys = call_user_func_array( [$this, 'keysFromInput'], func_get_args() );
        $this->setKey( $keys );
    }

    /**
     * Define the attributes for this object according to the respective docs.
     * 
     * @return void
     */
    abstract protected function configure();

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
     * considered optional.
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
     * 
     * @return string
     */
    public function toText()
    {
        $keys = $this->getAttributeNames();
        $max = 3 + max( array_map( 'strlen', $keys ) );

        $values = $this->toList();

        return array_reduce( $values, function ( $text, AttributeValue $item ) use ( $max ) {
            return $text .= sprintf( "%-{$max}s %s\n", $item->name() . ':', $item );
        }, '' );
    }

    /**
     * Convert object into an array of value objects.
     * 
     * @return AttributeValue[]
     */
    public function toList()
    {
        $defined = $this->getDefinedAttributes();

        $values = array_map( function ( \JsonSerializable $attr ) {
            return $attr->jsonSerialize();
        }, $defined );

        return array_reduce( $values, 'array_merge', [] );
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
     * @return AttributeInterface[]
     */
    protected function getDefinedAttributes()
    {
        $attributes = $this->getAttributes();

        return array_filter( $attributes, function ( AttributeInterface $attr ) {
            return $attr->isDefined();
        } );
    }

    /**
     * Filter all attributes that are mandatory.
     * 
     * @return AttributeInterface[]
     */
    protected function getRequiredAttributes()
    {
        $attributes = $this->getAttributes();

        return array_filter( $attributes, function ( AttributeInterface $attr ) {
            return $attr->isMandatory();
        } );
    }

// --- VALIDATION -----------------

    /**
     * Check if any of the required Attributes is undefined.
     * 
     * @return boolean
     */
    public function isValid()
    {
        $required = $this->getRequiredAttributes();

        return array_reduce( $required, function ( $bool, AttributeInterface $attr ) {
            return $bool and $attr->isDefined();
        }, true );
    }

    /**
     * Get the names of all mandatory attributes.
     * 
     * @return string[]
     */
    public function getMandatoryAttributes()
    {
        $required = $this->getRequiredAttributes();

        return array_keys( $required );
    }

    /**
     * Get the names of all mandatory attributes that are not defined.
     * 
     * @return string[]
     */
    public function getMissingAttributes()
    {
        $required = $this->getRequiredAttributes();

        $missing = array_filter( $required, function ( AttributeInterface $attr ) {
            return ! $attr->isDefined();
        } );

        return array_keys( $missing );
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
        return $this->toList();
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
     * @see http://php.net/IteratorAggregate
     * @return Iterator
     */
    public function getIterator()
    {
        $data = $this->getAttributes( true );
        return new \ArrayIterator( $data );
    }
}
