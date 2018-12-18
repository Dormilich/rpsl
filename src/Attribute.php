<?php
// Attribute.php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;
use Dormilich\RPSL\Transformers\DefaultTransformer;

/**
 * Define CRUD operations for attribute values and metadata for attribute handling.
 */
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
     * Object constructor. The calling object('s namespace) is needed for the 
     * `AttributeValue::object()` method to properly re-create the referenced 
     * RPSL object. It may be omitted if the RPSL objects are defined in the 
     * global namespace or the web service does not provide type information.
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
        $this->mandatory = filter_var( $mandatory, FILTER_VALIDATE_BOOLEAN );
        $this->multiple  = filter_var( $multiple,  FILTER_VALIDATE_BOOLEAN );

        if ( $obj ) {
            $this->namespace = $obj->getNamespace();
        }

        $this->transformer = new DefaultTransformer;
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
     * The negation of `isDefined()`.
     * 
     * @see Attribute::isDefined()
     * @return boolean
     */
    public function isEmpty()
    {
        return ! $this->isDefined();
    }

    /**
     * Whether the attribute is required/mandatory.
     *
     * @return boolean
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Whether the attribute is optional.
     *
     * @return boolean
     */
    public function isOptional()
    {
        return ! $this->isMandatory();
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
     * Whether the attribute is single.
     *
     * @return boolean
     */
    public function isSingle()
    {
        return ! $this->isMultiple();
    }

    /**
     * Get the current value(s) of the attribute.
     * If the value is unset NULL is returned, if the attribute
     * only allows a single value, that value is returned, otherwise an array.
     *
     * @return NULL|string|string[]
     */
    public function getValue()
    {
        $values = $this->getValues();

        if ( count( $values ) === 0 ) {
            $data = NULL;
        }
        elseif ( $this->isMultiple() ) {
            $data = $values;
        }
        else {
            $data = reset( $values );
        }

        return $data;
    }

    /**
     * Get the value strings for this attribute. Always returns an array.
     * 
     * @return string[]
     */
    public function getValues()
    {
        return array_map( 'strval', $this->values );
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

        if ( NULL !== $value ) {
            $this->setValues( $value );
        }

        return $this;
    }

    /**
     * Convert and save attribute values.
     * 
     * @param mixed $data 
     * @return void
     */
    private function setValues( $data )
    {
        if ( $this->isSingle() ) {
            $this->values = [];
        }

        $values = $this->loop( $data );

        foreach ( $values as $value ) {
            $this->values[] = $this->convert( $value );
        }
    }

    /**
     * Convert input into an iterable structure. For single-valued attributes 
     * the value is always wrapped into an array to guarantee that only one 
     * iteration is done.
     *
     * @param mixed $value
     * @return iterable
     */
    protected function loop( $value )
    {
        $value = $this->splitBlockText( $value );

        if ( $this->isSingle() ) {
            $data = [ $value ];
        }
        elseif ( is_array( $value ) ) {
            $data = $value;
        }
        elseif ( $value instanceof \Traversable ) {
            $data = $value;
        }
        else {
            $data = [ $value ];
        }

        return $data;
    }

    /**
     * Split block text into several lines. This is done independently from the 
     * _multiple_ setting since otherwise the created RPSL block text is likely 
     * to be invalid.
     * 
     * @param mixed $data 
     * @return mixed
     */
    private function splitBlockText( $data )
    {
        if ( is_string( $data ) and strpos( $data, "\n" ) !== false ) {
            $data = explode( "\n", $data );
        }

        return $data;
    }

// --- PHP INTERFACES -------------

    /**
     * Convert the list of values into a name+value object.
     *
     * @see http://php.net/JsonSerializable
     * @return AttributeValue[]
     */
    public function jsonSerialize()
    {
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
     * Convert negative offsets into positive ones.
     * 
     * @param integer|string $offset 
     * @return integer|string
     */
    private function fromReverseOffset( $offset )
    {
        if ( is_int( $offset ) and $offset < 0 ) {
            $offset += $this->count();
        }

        return $offset;
    }

    /**
     * Test if an attribute value exists at the given index.
     * 
     * @param integer $index 
     * @return boolean
     */
    private function hasOffset( $index )
    {
        return isset( $this->values[ $index ] );
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
        $offset = $this->fromReverseOffset( $offset );

        return $this->hasOffset( $offset );
    }

    /**
     * If an attribute value exist and if that value is a reference, return the 
     * referenced object otherwise the string value without comment.
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute value index.
     * @return ObjectInterface|string|NULL The attribute value.
     */
    public function offsetGet( $offset )
    {
        $offset = $this->fromReverseOffset( $offset );

        if ( ! $this->hasOffset( $offset ) ) {
            return NULL;
        }

        $value = $this->values[ $offset ];
        $data = $this->transformer->reverseTransform( $value );

        return $data;
    }

    /**
     * Add or update an existing Attibute value. 
     * 
     * @see http://php.net/ArrayAccess
     * @param string $offset Attribute value index.
     * @param mixed $value New Attribute value.
     * @return void
     */
    public function offsetSet( $offset, $value )
    {
        if ( NULL === $offset ) {
            $this->addValue( $value );
            return;
        }

        $offset = $this->fromReverseOffset( $offset );

        if ( $this->hasOffset( $offset ) ) {
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
        $offset = $this->fromReverseOffset( $offset );

        if ( $this->hasOffset( $offset ) ) {
            array_splice( $this->values, $offset, 1 );
        }
    }
}
