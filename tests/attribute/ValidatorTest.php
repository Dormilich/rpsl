<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\Validators as VLD;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage Value "quux" is not allowed for the [choice] attribute.
     */
    public function testChoiceValidator()
    {
        $choice = new VLD\Choice( [ 'foo', 'bar' ] );
        $attr = new Attribute( 'choice', true, false );
        $attr->test( $choice );

        $this->assertCount( 0, $attr );

        $attr->setValue( 'foo' );
        $this->assertSame( 'foo', $attr->getValue() );

        $attr->setValue( 'bar' );
        $this->assertSame( 'bar', $attr->getValue() );

        $attr->setValue( 'quux' );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage Value "quux" is not allowed for the [email] attribute.
     */
    public function testEmailValidator()
    {
        $attr = new Attribute( 'email', true, false );
        $attr->test( new VLD\Email );

        $attr->setValue( 'user@example.org' );
        $this->assertSame( 'user@example.org', $attr->getValue() );

        $attr->setValue( 'quux' );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage Value "quux" is not allowed for the [ip] attribute.
     */
    public function testIpValidator()
    {
        $attr = new Attribute( 'ip', true, false );
        $attr->test( new VLD\IpVersion );

        $attr->setValue( '198.51.100.35' );
        $this->assertSame( '198.51.100.35', $attr->getValue() );

        $attr->setValue( '2001:db8:792:50cd:7e11:e67:9d67:5b65' );
        $this->assertSame( '2001:db8:792:50cd:7e11:e67:9d67:5b65', $attr->getValue() );

        $attr->setValue( 'quux' );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage Value "2001:db8:792:50cd:7e11:e67:9d67:5b65" is not allowed for the [ip4] attribute.
     */
    public function testIp4Validator()
    {
        $attr = new Attribute( 'ip4', true, false );
        $attr->test( new VLD\IpVersion( 4 ) );

        $attr->setValue( '198.51.100.35' );
        $this->assertSame( '198.51.100.35', $attr->getValue() );

        $attr->setValue( '2001:db8:792:50cd:7e11:e67:9d67:5b65' );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage Value "198.51.100.35" is not allowed for the [ip6] attribute.
     */
    public function testIp6Validator()
    {
        $attr = new Attribute( 'ip6', true, false );
        $attr->test( new VLD\IpVersion( 6 ) );

        $attr->setValue( '2001:db8:792:50cd:7e11:e67:9d67:5b65' );
        $this->assertSame( '2001:db8:792:50cd:7e11:e67:9d67:5b65', $attr->getValue() );

        $attr->setValue( '198.51.100.35' );
    }

    public function testValidateCidr()
    {
        $attr = new Attribute( 'route6', true, false );
        $attr->test( new VLD\IpVersion( 6 ) );

        $attr->setValue( '2001:db8::/32' );
        $this->assertSame( '2001:db8::/32', $attr->getValue() );
    }

    public function testValidateRange()
    {
        $attr = new Attribute( 'inetnum', true, false );
        $attr->test( new VLD\IpVersion( 4 ) );

        $attr->setValue( '198.51.100.35 - 198.51.100.193' );
        $this->assertSame( '198.51.100.35 - 198.51.100.193', $attr->getValue() );
    }
}
