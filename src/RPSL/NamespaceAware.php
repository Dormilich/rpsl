<?php
// NamespaceAware.php

namespace Dormilich\RPSL;

interface NamespaceAware
{
    /**
     * Get the object’s namespace.
     * 
     * @return string
     */
    public function getNamespace();
}
