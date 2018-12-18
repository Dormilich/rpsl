<?php
// CallbackTransformer.php

namespace Dormilich\RPSL\Transformers;

use Dormilich\RPSL\AttributeValue;

/**
 * Do not transform anything.
 */
class CallbackTransformer implements TransformerInterface
{
    /**
     * @var callable Input transformer
     */
    protected $in = 'strval';

    /**
     * @var callable Output transformer
     */
    protected $out = 'strval';

    /**
     * Set up transformers. If a transformer is omitted, the default callbacks 
     * (`strval()`) are used.
     * 
     * @param callable|null $in Input transformer
     * @param callable|null $out Output transformer
     * @return self
     */
    public function __construct( callable $in = NULL, callable $out = NULL )
    {
        if ( $in ) {
            $this->in = $in;
        }
        if ( $out ) {
            $this->out = $out;
        }
    }

    /**
     * @inheritDoc
     */
    public function transform( $data )
    {
        return call_user_func( $this->in, $data );
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform( AttributeValue $value )
    {
        return call_user_func( $this->out, $value->value() );
    }
}
