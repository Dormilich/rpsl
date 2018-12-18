<?php
// DatetimeTransformer.php

namespace Dormilich\RPSL\Transformers;

use Dormilich\RPSL\AttributeValue;

/**
 * Convert date object to timestamp using the ISO-8601 format (and vice versa).
 */
class DatetimeTransformer implements TransformerInterface
{
    /**
     * @var string Date class name
     */
    protected $dateClass;

    /**
     * @var DateTimeZone User timezone
     */
    protected $timezone;

    /**
     * When a date object is passed, the transformed attribute value will be a 
     * date object of this class and timezone. 
     * 
     * @param DateTimeInterface|null $date Reference date object.
     * @return self
     */
    public function __construct( \DateTimeInterface $date = NULL )
    {
        if ( NULL === $date ) {
            $date = new \DateTimeImmutable( 'now' );
        }

        $this->dateClass = get_class( $date );
        $this->timezone = $date->getTimezone();
    }

    /**
     * Reads a datetime instance/string and converts it into ISO format. Emits 
     * a notice if the value is not a valid timestamp.
     * 
     * @param mixed $value Input data.
     * @return string ISO date.
     */
    public function transform( $data )
    {
        try {
            $timestamp = $this->createTimestamp( $data );
        } catch ( \Exception $e ) {
            trigger_error( $e->getMessage(), E_USER_NOTICE );
            $timestamp = $data;
        }

        return $timestamp;
    }

    /**
     * Creates a new date instance of the value. Uses the configured timezone, 
     * if possible. If the value is not a date, it is returned as string. Emits 
     * a notice if the value is not a valid timestamp.
     * 
     * @param AttributeValue $value 
     * @return DateTimeInterface|NULL
     */
    public function reverseTransform( AttributeValue $value )
    {
        if ( $value->isEmpty() ) {
            return NULL;
        }

        try {
            $timestamp = $this->createTimestamp( $value->value() );
            $datetime = $this->createDatetime( $timestamp );
        } catch (\Exception $e) {
            trigger_error( $e->getMessage(), E_USER_NOTICE );
            $datetime = (string) $value;
        }

        return $datetime;
    }

    /**
     * Create an ISO-8601 formatted date string optionally using the user's timezone.
     * 
     * Note: `DateTime` ignores the given timezone if a timezone is implied 
     *       by the timestamp. 
     * 
     * @param mixed $date Date string.
     * @return string ISO-8601 formatted date string
     * @throws Exception Invalid argument for DateTime constructor.
     */
    private function createTimestamp( $date )
    {
        if ( $date instanceof \DateTimeInterface ) {
            $datetime = $date;
        }
        else {
            $datetime = date_create_from_format( DATE_W3C, $date );
        }

        if ( false === $datetime ) {
            $datetime = new \DateTime( $date, $this->timezone );
        }

        return $datetime->format( DATE_W3C );
    }

    /**
     * Create a date object from a timestamp string.
     * 
     * @param string $timestamp 
     * @return DateTimeInterface
     */
    private function createDatetime( $timestamp )
    {
        $class = $this->dateClass;

        $datetime = new $class( $timestamp );
        $datetime = $this->setTimezone( $datetime );

        return $datetime;
    }

    /**
     * Set the timezone on a date object. As of PHP 5.5.8 the `DateTimeInterface` 
     * cannot be implemented directly, therefore you need to extend `DateTime` or 
     * `DateTimeImmutable`, which both implement a timezone setter.
     * 
     * @param DateTimeInterface $date 
     * @return DateTimeInterface
     */
    private function setTimezone( \DateTimeInterface $date )
    {
        return $date->setTimezone( $this->timezone ) ?: $date;
    }
}
