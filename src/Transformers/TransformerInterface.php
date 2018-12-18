<?php
// TransformerInterface.php

namespace Dormilich\RPSL\Transformers;

use Dormilich\RPSL\AttributeValue;

/**
 * A data transformer applies data type conversion between the input data and 
 * the data used to be read by `AttributeValue`. 
 */
interface TransformerInterface
{
    /**
     * Transform the data from the input format to text format. 
     * 
     * @param mixed $data Input data.
     * @return string Parsable data.
     */
    public function transform( $data );

    /**
     * Transform the internal data into a convenient data format for handling in PHP.
     * 
     * @param AttributeValue $value Internal data.
     * @return mixed Output data.
     */
    public function reverseTransform( AttributeValue $value );
}
