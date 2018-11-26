<?php
// ObjectName.php

namespace Dormilich\RPSL\Traits;

trait ObjectName
{
    /**
     * @var string The type of the object.
     */
    private $name;

    /**
     * Get the name of the current RPSL object.
     * 
     * @return string RPSL object name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the object type based on the class name.
     * 
     * @return void
     */
    protected function setName()
    {
        $name = (new \ReflectionClass( $this ))->getShortName();

        $type = preg_replace_callback( '~[A-Z]~', function ( $match ) {
            return '-' . strtolower( $match[ 0 ] );
        }, $name );

        $this->name = trim( $type, '-' );
    }

    /**
     * Get the object’s namespace.
     * 
     * @return string
     */
    public function getNamespace()
    {
        return (new \ReflectionClass( $this ))->getNamespaceName();
    }
}
