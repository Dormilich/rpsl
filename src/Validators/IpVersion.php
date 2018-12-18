<?php
// Ip.php

namespace Dormilich\RPSL\Validators;

/**
 * Test a CIDR or IP range conforms to a specific IP version.
 */
class IpVersion
{
    const V4 = '4';
    const V6 = '6';

    /**
     * @var integer A FILTER_FLAG_* combination
     */
    private $flags;

    /**
     * Create validator with the allowed values. The values may be of any type 
     * as long as they can be converted to a string.
     * 
     * @param array $setup The configuration for the validator.
     * @return self
     */
    public function __construct( $version = 'all' )
    {
        $this->flags = $this->getFlags( (string) $version );
    }

    /**
     * @see https://secure.php.net/manual/en/language.oop5.magic.php#object.invoke
     * @param scalar $value The value to test.
     * @return boolean
     */
    public function __invoke( $value )
    {
        return array_reduce( $this->getIP( $value ), function ( $bool, $ip ) {
            return $bool and filter_var( $ip, FILTER_VALIDATE_IP, $this->flags ) !== false;
        }, true );
    }

    /**
     * Convert the IP version string into a set of filter flags.
     * 
     * @param string $version 
     * @return integer|NULL
     */
    protected function getFlags( $version )
    {
        switch ( $version ) {
            case self::V4:
               return FILTER_FLAG_IPV4;

            case self::V6:
               return FILTER_FLAG_IPV6;

            default:
                return NULL;
        }
    }

    /**
     * Extract IP addresses from CIDR or IP range.
     * 
     * @param string $value 
     * @return string[]
     */
    protected function getIP( $value )
    {
        if (preg_match_all( '#^([0-9a-f:.]+)/\d{1,3}$#', $value, $match ) ) {
            return $match[ 1 ];
        }

        if ( preg_match_all( '/[0-9a-f:.]+/', $value, $match ) ) {
            return $match[ 0 ];
        }

        return [ NULL ];
    }
}
