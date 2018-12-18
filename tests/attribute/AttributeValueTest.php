<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\NamespaceAware;
use Dormilich\RPSL\ObjectInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class AttributeValueTest extends TestCase
{
    use PHPMock;

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
        $data = $this->createMock( ObjectInterface::class );
        $data->method( 'getName' )->willReturn( 'person' );
        $data->method( 'getHandle' )->willReturn( 'TEST12-RIPE' );

        $obj = new AttributeValue( $data );

        $this->assertSame( 'TEST12-RIPE', $obj->value() );
    }

    public function testAcceptsSeparatedWebserviceData()
    {
        $obj = new AttributeValue( 'phpunit', 'test', null );

        $this->assertSame( 'phpunit', $obj->value() );
        $this->assertSame( 'test', $obj->comment() );
        $this->assertSame( 'phpunit # test', (string) $obj );
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

    // object recreation

    public function testGetObject()
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     */
    public function testGetNamespacedObject()
    {
        // the return value will cause an exception, which is ok
        // since we do not want the fake class to actually load
        $fn = $this->getFunctionMock( 'Dormilich\RPSL', 'class_exists' );
        $fn->expects( $this->once() )
            ->with( $this->identicalTo( 'Ripe\Role' ) )
            ->willReturn( false );

        $obj = $this->createMock( [ ObjectInterface::class, NamespaceAware::class ] );
        $obj->method( 'getHandle' )->willReturn( 'phpunit' );
        $obj->method( 'getName' )->willReturn( 'role' );
        $obj->method( 'getNamespace' )->willReturn( 'Ripe' );

        $val = new AttributeValue( $obj );
        $val->object();
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage The object type [foo-bar] cannot be converted to an RPSL object.
     */
    public function testGetInvalidObjectFails()
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getHandle' )->willReturn( 'TEST12-RPSL' );
        $obj->method( 'getName' )->willReturn( 'foo-bar' );

        $val = new AttributeValue( $obj );
        $val->object();
    }
}
