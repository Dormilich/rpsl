<?php
// ValueTransformation.php

namespace Dormilich\RPSL\Traits;

use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\DataInterface;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;
use Dormilich\RPSL\Transformers\TransformerInterface;

/**
 * @uses Dormilich\RPSL\AttributeInterface
 */
trait ValueTransformation
{
    /**
     * @var callable Validation callback.
     */
    protected $validator = 'is_string';

    /**
     * @var TransformerInterface Value transformation.
     */
    protected $transformer;

    /**
     * Set the validator callback that the input value is tested against.
     * Validating callbacks should throw an exception if the input is invalid.
     * The callback must return TRUE if the value is valid.
     *
     * @param callable $callback The callback function is passed the input
     *          value string as parameter and must return a boolean.
     * @return self
     */
    public function test( callable $callback )
    {
        $this->validator = $callback;

        return $this;
    }

    /**
     * Set the transformer callback that a user-provided input is run through
     * after converting it to a string. This will not affect values that are
     * retrieved from RPSL objects.
     *
     * @param callable $callback The callback function is passed the input
     *          value string as parameter and must return the modified string.
     * @return self
     */
    public function apply( TransformerInterface $transformer )
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * Converts a single value to an AttributeValue object.
     *
     * @param string|object $value
     * @return AttributeValue
     * @throws InvalidDataTypeException Invalid data type of the value.
     * @throws InvalidValueException Validation failed.
     */
    protected function convert( $value )
    {
        $obj = $this->transform( $value );
        $obj->setAttribute( $this );

        if ( $this->validate( $obj ) ) {
            return $obj;
        }

        $msg = 'Value "%s" is not allowed for the [%s] attribute.';
        $msg = sprintf( $msg, $obj->value(), $this->getName() );
        throw new InvalidValueException( $msg );
    }

    /**
     * Convert input value into an AttributeValue object.
     * 
     * @param mixed $value 
     * @return AttributeValue
     * @throws InvalidDataTypeException Invalid data type of the value.
     */
    protected function transform( $value )
    {
        if ( $value instanceof AttributeValue ) {
            return clone $value;
        }

        if ( $value instanceof ObjectInterface or $value instanceof DataInterface ) {
            return new AttributeValue( $value );
        }

        $data = $this->transformer->transform( $value );

        if ( is_string( $data ) ) {
            return new AttributeValue( $data );
        }

        $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
        $msg = 'The [%s] attribute does not allow the %s data type.';
        $msg = sprintf( $msg, $this->getName(), $type );
        throw new InvalidDataTypeException( $msg );
    }

    /**
     * Run a value check before saving the value.
     *
     * @param AttributeValue $obj Attribute value.
     * @return boolean Validation success.
     */
    protected function validate( AttributeValue $obj )
    {
        return call_user_func( $this->validator, $obj->value() );
    }
}
