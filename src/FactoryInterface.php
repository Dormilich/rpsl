<?php

namespace Dormilich\RPSL;

use Exception;

interface FactoryInterface
{
    /**
     * Create a new RPSL object from its type value.
     *
     * @param string $type Type value of the RPSL object.
     * @param string|null $handle
     * @return ObjectInterface
     * @throws Exception
     */
    public function create(string $type, ?string $handle = null): ObjectInterface;
}
