<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\ObjectInterface;
use PHPUnit\Framework\TestCase;

class AttributeValueTest extends TestCase
{
    public function testAcceptsString()
    {
        $obj = new AttributeValue( 'test' );

        $this->assertSame( 'test', $obj->value() );
        $this->assertNull( $obj->comment() );
        $this->assertNull( $obj->object() );
    }

    public function testAcceptsCommentedString()
    {
        $obj = new AttributeValue( 'foo # bar' );

        $this->assertSame( 'foo', $obj->value() );
        $this->assertSame( 'bar', $obj->comment() );
        $this->assertNull( $obj->object() );
    }

    public function testAcceptsRpslObject()
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getName' )->willReturn( 'person' );
        $obj->method( 'getHandle' )->willReturn( 'TEST12-RIPE' );

        $obj = new AttributeValue( $obj );

        $this->assertSame( 'TEST12-RIPE', $obj->value() );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidDataTypeException
     * @expectedExceptionMessage Data type [boolean] is not supported as attribute value.
     */
    public function testInvalidInputFails()
    {
        new AttributeValue( false );
    }

    /**
     * @depends testAcceptsString
     */
    public function testValueIsDefined()
    {
        $val = new AttributeValue( 'test' );
        $this->assertTrue( $val->isDefined() );
    }

    /**
     * @depends testAcceptsString
     */
    public function testEmptyValueIsNotDefined()
    {
        $val = new AttributeValue( '' );
        $this->assertFalse( $val->isDefined() );
    }

    /**
     * @depends testAcceptsString
     */
    public function testCommentOnlyIsNotDefined()
    {
        $val = new AttributeValue( '# comment' );
        $this->assertFalse( $val->isDefined() );
    }

    /**
     * @depends testAcceptsString
     */
    public function testNonTextCommentIsDiscarded()
    {
        $val = new AttributeValue( '############' );
        $this->assertNull( $val->comment() );
    }

    public function testGetValueString()
    {
        $obj = new AttributeValue( 'test' );

        $this->assertSame( 'test', ( string ) $obj );
    }

    public function testGetCommentedValueString()
    {
        $obj = new AttributeValue( 'test #filtered' );

        $this->assertSame( 'test # filtered', ( string ) $obj );
    }

    public function testCommentOnlyIsDiscarded()
    {
        $obj = new AttributeValue( '#test' );

        $this->assertSame( '', ( string ) $obj );
    }

    public function testGetEmptyObjectString()
    {
        $obj = new AttributeValue( '' );

        $this->assertSame( '', ( string ) $obj );
    }

    // JSON

    public function testAcceptsJsonObject()
    {
        $json = file_get_contents( __DIR__ . '/_fixtures/attribute.json' );
        $json = json_decode( $json, false );

        $obj = new AttributeValue( $json );

        $this->assertSame( 'PHPUNIT-TEST', $obj->value() );
        $this->assertSame( 'foo', $obj->type() );
        $this->assertSame( 'Filtered', $obj->comment() );
    }

    public function testAcceptsJsonArray()
    {
        $json = file_get_contents( __DIR__ . '/_fixtures/attribute.json' );
        $json = json_decode( $json, true );

        $obj = new AttributeValue( $json );

        $this->assertSame( 'PHPUNIT-TEST', $obj->value() );
        $this->assertSame( 'foo', $obj->type() );
        $this->assertSame( 'Filtered', $obj->comment() );
    }

    // object recreation

    public function testGetRpslObject()
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getHandle' )->willReturn( 'phpunit' );
        $obj->method( 'getName' )->willReturn( 'exception' );

        $val = new AttributeValue( $obj );
        $exc = $val->object();

        $this->assertInstanceOf( 'Exception', $exc );
        $this->assertSame( 'phpunit', $exc->getMessage() );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage The object type [foo-bar] cannot be converted to an RPSL object.
     */
    public function testGetInvalidRpslObjectFails()
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getHandle' )->willReturn( 'TEST12-RPSL' );
        $obj->method( 'getName' )->willReturn( 'foo-bar' );

        $val = new AttributeValue( $obj );
        $val->object();
    }
}
