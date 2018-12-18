<?php
// Email.php

namespace Dormilich\RPSL\Validators;

/**
 * Test a value if it is a syntactically valid email address.
 */
class Email
{
    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
    }
}
