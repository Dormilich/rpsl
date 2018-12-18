<?php
// DefaultTransformer.php

namespace Dormilich\RPSL\Transformers;

use Dormilich\RPSL\AttributeValue;

/**
 * Convert input to parsable string and attribute value to output.
 */
class DefaultTransformer implements TransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform( $data )
    {
        if ( is_bool( $data ) ) {
            $value = $data ? 'true' : 'false';
        }
        elseif ( is_scalar( $data ) ) {
            $value = (string) $data;
        }
        elseif ( is_object( $data ) and method_exists( $data, '__toString' ) ) {
            $value = (string) $data;
        }
        else {
            $value = $data;
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( AttributeValue $value )
    {
        if ( $value->isEmpty() ) {
            $data = NULL;
        }
        elseif ( $obj = $value->object() ) {
            $data = $obj;
        }
        else {
            $data = $value->value();
        }

        return $data;
    }
}
