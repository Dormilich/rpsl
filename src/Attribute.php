<?php
// Attribute.php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;

class Attribute implements AttributeInterface, NamespaceAware, \ArrayAccess, \Countable, \Iterator, \JsonSerializable
{
    use Traits\ValueTransformation;

    /**
     * @var string Attribute name.
     */
    protected $name;

    /**
     * @var string Implementation namespace.
     */
    protected $namespace;

    /**
     * @var AttributeValue[] Attribute values.
     */
    protected $values = [];

    /**
     * @var boolean Whether this attribute is mandatory.
     */
    protected $mandatory;

    /**
     * @var boolean Whether this attribute allows multiple values.
     */
    protected $multiple;

    /**
     * Object constructor.
     *
     * @param string $name Attribute name.
     * @param boolean $mandatory If the attribute is mandatory/required.
     * @param boolean $multiple If the attribute allows multiple values.
     * @param NamespaceAware $obj The calling object.
     * @return self
     */
    public function __construct( $name, $mandatory, $multiple, NamespaceAware $obj = NULL )
    {
        $this->name = (string) $name;
        $this->mandatory = filter_var($mandatory, FILTER_VALIDATE_BOOLEAN);
        $this->multiple  = filter_var($multiple,  FILTER_VALIDATE_BOOLEAN);

        if ( $obj ) {
            $this->namespace = $obj->getNamespace();
        }
    }

    /**
     * Get the name of the attribute.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the RPSL object’s namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Whether the attribute is populated with data (i.e. not empty) and if the 
     * values contain more than empty strings.
     *
     * @return boolean
     */
    public function isDefined()
    {
        return array_reduce( $this->values, function ( $bool, AttributeValue $value ) {
            return $bool or $value->isDefined();
        }, false );
    }

    /**
     * Whether the attribute is required/mandatory.
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->mandatory;
    }

    /**
     * Whether the attribute allows multiple values.
     *
     * @return boolean
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * Get the current value(s) of the attribute.
     * If the value is unset NULL is returned, if the attribute
     * only allows a single value, that value is returned, otherwise an array.
     *
     * @return mixed
     */
    public function getValue()
    {
        if ( count( $this->values ) === 0 ) {
            return NULL;
        }

        $values = array_map( 'strval', $this->values );

        if ( $this->multiple ) {
            return $values;
        }

        return reset( $values );
    }

    /**
     * Set the value(s) of the attribute. Each value must be either a scalar
     * or a stringifiable object. Passing an array to a single-valued attribute
     * will cause a data type error.
     *
     * @param mixed $value A string or stringifyable object or an array thereof.
     * @return self
     * @throws InvalidDataTypeException Invalid data type of the value(s).
     */
    public function setValue( $value )
    {
        $this->values = [];
        $this->addValue( $value );

        return $this;
    }

    /**
     * Add value(s) to the attribute. If the attribute does not allow multiple
     * values the value is replaced instead. The value(s) must be stringifiable.
     *
     * If NULL is passed, execution is skipped. That is, `setValue(NULL)` will
     * reset the Attribute while `addValue(NULL)` has no effect. Passing an
     * array to a single-valued attribute will cause a data type error.
     *
     * If a multiline block of text is passed, treat it as an array of text lines.
     *
     * For single-valued attributes `addValue()` and `setValue()` work identically.
     *
     * @param mixed $value A string or stringifyable object or an array thereof.
     * @return self
     * @throws InvalidDataTypeException Invalid data type of the value(s).
     */
    public function addValue( $value )
    {
        if ( $value instanceof AttributeInterface ) {
            $value = $value->getValue();
        }

        if ( NULL === $value ) {
            return $this;
        }

        foreach ( $this->loop( $value ) as $v ) {
            $this->values[] = $this->convert( $v );
        }

        return $this;
    }

    /**
     * Convert input into an iterable structure.
     *  - Block text is converted into an array
     *  - single valued attributes are reset and the value is wrapped in an array
     *  - any input left is wrapped in an array
     *
     * @param mixed $value
     * @return array
     */
    protected function loop( $value )
    {
        // split block text regardless of attribute type
        // otherwise the created RPSL block text is likely to be invalid
        if ( is_string( $value ) and strpos( $value, "\n" ) !== false ) {
            $value = explode( "\n", $value );
        }
        // wrapping the supposedly-single value in an array makes sure that
        // only a single iteration is done, even if an array is passed
        if ( ! $this->multiple ) {
            $this->values = [];
            $value = [ $value ];
        }
        elseif ( ! is_array( $value ) ) {
            $value = [ $value ];
        }

        return $value;
    }

// --- PHP INTERFACES -------------

    /**
     * Convert the list of values into a name+value object.
     *
     * @see http://php.net/JsonSerializable
     * @return array
     */
    public function jsonSerialize()
    {
        // empty lines might be intentional ...
        return $this->values;
    }

    /**
     * Number of values assigned.
     *
     * @see http://php.net/Countable
     * @return integer
     */
    public function count()
    {
        return count( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function rewind()
    {
        reset( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return string
     */
    public function current()
    {
        return current( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return integer
     */
    public function key()
    {
        return key( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return void
     */
    public function next()
    {
        next( $this->values );
    }

    /**
     * @see http://php.net/Iterator
     * @return boolean
     */
    public function valid()
    {
        return NULL !== key( $this->values );
    }

    /**
     * Checks if an Attribute value exists at the given position.
     * 
     * @see http://php.net/ArrayAccess
     * @param mixed $offset The array key.
     * @return boolean
     */
    public function offsetExists( $offset )
    {
        return isset( $this->values[ $offset ] );
    }

    /**
     * If an attribute value exist and if that value is a reference, return the 
     * referenced object otherwise the string value without comment.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute value index.
     * @return Object|string|NULL The attribute value.
     */
    public function offsetGet( $offset )
    {
        if ( $this->offsetExists( $offset ) ) {
            $value = $this->values[ $offset ];
            return $value->object() ?: $value->value();
        }

        return NULL;
    }

    /**
     * Update an existing Attibute value. 
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute value index.
     * @param mixed $value New Attribute value.
     * @return void
     */
    public function offsetSet( $offset, $value )
    {
        if ( $this->offsetExists( $offset ) ) {
            $this->values[ $offset ] = $this->convert( $value );
        }
    }

    /**
     * Remove an Attribute value.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute value index.
     * @return void
     */
    public function offsetUnset( $offset )
    {
        if ( $this->offsetExists( $offset ) ) {
            array_splice( $this->values, $offset, 1 );
        }
    }
}
