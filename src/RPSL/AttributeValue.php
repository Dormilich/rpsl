<?php
// AttributeValue.php

namespace Dormilich\RPSL;

use Dormilich\RPSL\Exceptions\InvalidDataTypeException;
use Dormilich\RPSL\Exceptions\InvalidValueException;

final class AttributeValue implements \JsonSerializable
{
    /**
     * @var string Name of the attribute containing this object.
     */
    protected $attribute;

    /**
     * @var string The attribute value.
     */
    protected $value = '';

    /**
     * @var string The comment of the attribute value.
     */
    protected $comment;

    /**
     * @var string RPSL object type.
     */
    protected $type;

    /**
     * @var string Namespace of the attribute’s object.
     */
    protected $namespace;

    /**
     * Create value object from attribute value string or RPSL object. 
     * 
     * Note: This object is used internally and should not be set up manually. 
     * 
     * @param string|Object $value Attribute value or RPSL object.
     * @return self
     * @throws InvalidDataTypeException Invalid argument.
     */
    public function __construct( $value )
    {
        // standard input
        if ( is_string( $value ) ) {
            $this->setFromString( $value );
        }
        // array from JSON
        elseif ( is_array( $value ) ) {
            $this->setFromArray( $value );
        }
        // object from JSON
        elseif ( $value instanceof \stdClass ) {
            $this->setFromArray( get_object_vars( $value ) );
        }
        // RPSL object
        elseif ( $value instanceof ObjectInterface ) {
            $this->setFromObject( $value );
        }
        else {
            $msg = 'Data type [%s] is not supported as attribute value.';
            $type = is_object( $value ) ? get_class( $value ) : gettype( $value );
            throw new InvalidDataTypeException( sprintf( $msg, $type ) );
        }
        // delete comment if there is no value
        if ( strlen( $this->value ) === 0 ) {
            $this->comment = NULL;
        }
    }

    /**
     * Switch to set the attribute name internally, so value objects can be cloned.
     * 
     * @return void
     */
    public function __clone()
    {
        $this->attribute = NULL;
    }

    /**
     * The standard way of passing an attribute value. If a comment is contained, 
     * it will be separated from the value.
     * 
     * @param string $input 
     * @return void
     */
    protected function setFromString( $input )
    {
        if ( strpos( $input, '#' ) === false ) {
            $value = $input;
        }
        else {
            list( $value, $comment ) = explode( '#', $input, 2 );

            $this->setComment( $comment );
        }

        $this->setValue( $value );
    }

    /**
     * Pass an RPSL object as value. It will be serialised using the object's 
     * primary key and type.
     * 
     * @param ObjectInterface $input 
     * @return void
     */
    protected function setFromObject( ObjectInterface $input )
    {
        $this->setFromString( $input->getHandle() );
        // the handle SHOULD NOT contain a comment ...
        $this->comment = NULL;

        $this->type = $input->getName();

        if ( $input instanceof NamespaceAware ) {
            $this->namespace = $input->getNamespace();
        }
    }

    /**
     * This is only supposed to be used by the web service when creating objects 
     * from the response JSON.
     * 
     * @param array $input 
     * @return void
     */
    protected function setFromArray( array $input )
    {
        if ( isset( $input[ 'value' ] ) ) {
            $this->setValue( (string) $input[ 'value' ] );
        }

        if ( isset( $input[ 'comment' ] ) ) {
            $this->setComment( (string) $input[ 'comment' ] );
        }

        if ( isset( $input[ 'referenced-type' ] ) ) {
            $this->type = (string) $input[ 'referenced-type' ];
        }
    }

    /**
     * Internal setter for the value property.
     * 
     * @param string $value 
     * @return void
     */
    protected function setValue( $value )
    {
        // left-side whitespace may be intentional
        $value = rtrim( $value );

        $this->value = $value;
    }

    /**
     * Internal setter for the comment property.
     * 
     * @param string $comment 
     * @return void
     */
    protected function setComment( $comment )
    {
        $comment = trim( $comment, "# \t\n\r\0\x0B" );

        if ( strlen( $comment ) > 0 ) {
            $this->comment = $comment;
        }
    }

    /**
     * Sets the name of the attribute. This is required for JSON encoding. This 
     * value can only be set once. This method is only supposed to be used 
     * internally.
     * 
     * @param AttributeInterface $attr 
     * @return void
     */
    public function setAttribute( AttributeInterface $attr )
    {
        if ( ! $this->attribute ) {
            $this->attribute = $attr->getName();
        }

        if ( ! $this->namespace and $attr instanceof NamespaceAware ) {
            $this->namespace = $attr->getNamespace();
        }
    }

    /**
     * Return the string value of the attribute. If a comment is set, append the 
     * comment to the value.
     * 
     * @return string
     */
    public function __toString()
    {
        $string = $this->value;

        if ( $this->comment ) {
            $string .= ' # ' . $this->comment;
        }

        return $string;
    }

    /**
     * @see http://php.net/jsonserializable
     * @return stdClass
     */
    public function jsonSerialize()
    {
        $json = new \stdClass;
        $json->name  = $this->attribute;
        $json->value = $this->value;

        if ( strlen( $this->comment ) > 0 ) {
            $json->comment = $this->comment;
        }

        return $json;
    }

    /**
     * Returns TRUE if a value is set. Comments are expected to comment the 
     * value, not the object. So if there is a comment but no value, the field 
     * is considered empty.
     * 
     * @return boolean
     */
    public function isDefined()
    {
        return strlen( $this->value ) > 0;
    }

    /**
     * Get the name of the associated attribute.
     * 
     * @return type
     */
    public function name()
    {
        return $this->attribute;
    }

    /**
     * Get the bare attribute value.
     * 
     * @return type
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Get the comment string.
     * 
     * @return string
     */
    public function comment()
    {
        return $this->comment;
    }

    /**
     * Get the type value.
     * 
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Get an RPSL object for lookup purposes (i.e. only the primary loopup key is set).
     * 
     * @return Object Empty RPSL object.
     * @throws InvalidValueException Invalid object type.
     */
    public function object()
    {
        if ( ! $this->type ) {
            return NULL;
        }

        $class = $this->getClass( $this->type );

        if ( class_exists( $class ) ) {
            return new $class( $this->value );
        }

        $msg = 'The object type [%s] cannot be converted to an RPSL object.';
        throw new InvalidValueException( sprintf( $msg, $this->type ) );
    }

    /**
     * Convert object type into object class name.
     * 
     * @param string $type Object name to convert.
     * @return string Object class.
     */
    protected function getClass( $type )
    {
        $name = preg_replace_callback( '/-?\b([a-z])/', function ( $match ) {
            return strtoupper( $match[ 1 ] );
        }, $type );

        return $this->namespace . '\\' . $name;
    }
}
