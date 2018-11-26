<?php
// ValueTransformation.php

namespace Dormilich\RPSL\Traits;

use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;

/**
 * @uses Dormilich\RPSL\AttributeInterface
 */
trait ValueTransformation
{
    /**
     * @var callable Validation callback.
     */
    protected $validator;

    /**
     * @var callable String input transformation.
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
    public function apply( callable $callback )
    {
        $this->transformer = $callback;

        return $this;
    }

    /**
     * Apply some last-chance modification to the value before it enters validation.
     *
     * @param mixed $value
     * @return string
     */
    protected function transform( $value )
    {
        if ( is_callable( $this->transformer ) ) {
            return call_user_func( $this->transformer, $value );
        }
        return $value;
    }

    /**
     * Run a value check before saving the value.
     *
     * @param AttributeValue $obj Attribute value.
     * @return boolean Validation success.
     */
    protected function validate( AttributeValue $obj )
    {
        if ( is_callable( $this->validator ) ) {
            return call_user_func( $this->validator, $obj->value() );
        }
        return true;
        
    }

    /**
     * Converts a single value to an AttributeValue object.
     *
     * @param string|object $value
     * @return AttributeValue
     * @throws InvalidValueException Validation failed.
     */
    protected function convert( $value )
    {
        if ( $value instanceof AttributeValue ) {
            $obj = clone $value;
        }
        else {
            if ( ! $value instanceof ObjectInterface ) {
                $value = $this->stringify( $value );
            }
            $obj = new AttributeValue( $value );
        }

        $obj->setAttribute( $this );

        if ( $this->validate( $obj ) ) {
            return $obj;
        }

        $msg = 'Value "%s" is not allowed for the [%s] attribute.';
        throw new InvalidValueException( sprintf( $msg, $obj->value(), $this->getName() ) );
    }

    /**
     * Converts a single value to a string.
     *
     * @param mixed $value A scalar or transformable object.
     * @return string Converted value.
     * @throws InvalidDataTypeException Invalid data type of the value(s).
     */
    final protected function stringify( $value )
    {
        $value = $this->transform( $value );

        if ( is_scalar( $value ) or ( is_object( $value ) and method_exists( $value, '__toString' ) ) ) {
            return (string) $value;
        }

        $msg = 'The [%s] attribute does not allow the %s data type.';
        $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
        throw new InvalidDataTypeException( sprintf( $msg, $this->getName(), $type ) );
    }
}
