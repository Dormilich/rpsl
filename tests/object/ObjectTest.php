<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\AttributeInterface as Attr;
use Dormilich\RPSL\Object;
use PHPUnit\Framework\TestCase;

class ObjectTest extends TestCase
{
    private function object( $handle )
    {
        return $this->getMockForAbstractClass( Object::class, [ $handle ], 'Foo' );
    }

    private function define( Object $obj, $name, $multiple = false, $mandatory = true )
    {
        $rm = new \ReflectionMethod( $obj, 'define' );
        $rm->setAccessible( true );
        $rm->invoke( $obj, $name, $mandatory, $multiple );
    }

    private function generate( Object $obj, $name, $multiple = false )
    {
        $rm = new \ReflectionMethod( $obj, 'generated' );
        $rm->setAccessible( true );
        $rm->invoke( $obj, $name, $multiple );
    }

    public function testSetupObject()
    {
        $obj = $this->object( 'bar' );

        $this->assertSame( 'foo', $obj->getName() );
        $this->assertSame( 'bar', $obj->getHandle() );
    }

    public function testGetPrimaryKeyWithoutComment()
    {
        $obj = $this->object( 'bar # test' );

        $this->assertSame( 'bar # test', $obj->get( 'foo' ));
        $this->assertSame( 'bar', $obj->getHandle() );
        $this->assertSame( 'bar', (string) $obj );
    }

    public function testEmptyKeyReturnsUndefinedHandle()
    {
        $obj = $this->object( 'bar' );
        unset( $obj[ 'foo' ] );

        $this->assertNull( $obj->getHandle() );
    }

    // repeat previous test with composite key

    public function testGetPrimaryKeyNames()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'bar' );

        $names = $obj->getPrimaryKeyNames();

        $this->assertCount( 1, $names );
        $this->assertEquals( [ 'foo' ], $names );
    }

    public function testGetAllAttributeNames()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'bar' );

        $names = $obj->getAttributeNames();

        $this->assertCount( 2, $names );
        $this->assertEquals( [ 'foo', 'bar' ], $names );
    }

    public function testGetAttributeObject()
    {
        $obj = $this->object( 'bar' );
        $attr = $obj->attr( 'foo' );

        $this->assertInstanceOf( Attribute::class, $attr );
    }

    public function testGetGeneratedAttributeObject()
    {
        $obj = $this->object( 'bar' );
        $this->generate( $obj, 'last-modified', false );

        $attr = $obj->attr( 'last-modified' );

        $this->assertInstanceOf( Attribute::class, $attr );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidAttributeException
     * @expectedExceptionMessage Attribute "1" is not defined for the FOO object.
     */
    public function testGetUndefinedAttributeFails()
    {
        $obj = $this->object( 'bar' );
        $attr = $obj->attr( 1 );
    }

    public function testSetMultipleAttributeValue()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'comment', true, false );

        $obj->set( 'comment', 'x' );
        $this->assertEquals( ['x'], $obj->attr( 'comment' )->getValue() );

        $obj->set( 'comment', 'y' );
        $this->assertEquals( ['y'], $obj->attr( 'comment' )->getValue() );
    }

    public function testSetSingleAttributeValue()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );

        $this->assertFalse( $obj->attr( 'name' )->isMultiple() );

        $obj->set( 'name', 'x' );
        $this->assertEquals( 'x', $obj->attr( 'name' )->getValue() );

        $obj->set( 'name', 'y' );
        $this->assertEquals( 'y', $obj->attr( 'name' )->getValue() );
    }

    public function testAddMultipleAttributeValue()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'comment', true, false );

        $this->assertTrue( $obj->attr( 'comment' )->isMultiple() );

        $obj->add( 'comment', 'x' );
        $this->assertEquals( ['x'], $obj->attr( 'comment' )->getValue() );

        $obj->add( 'comment', 'y' );
        $this->assertEquals( ['x', 'y'], $obj->attr( 'comment' )->getValue() );
    }

    public function testAddSingleAttributeValue()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );

        $obj->add( 'name', 'x' );
        $this->assertEquals( 'x', $obj->attr( 'name' )->getValue() );

        $obj->add( 'name', 'y' );
        $this->assertEquals( 'y', $obj->attr( 'name' )->getValue() );
    }

    public function testGetAttributeValue()
    {
        $obj = $this->object( 'bar' );

        $this->assertSame( $obj->attr( 'foo' ), $obj[ 'foo' ] );
        $this->assertSame( 'bar', $obj->get( 'foo' ));
        $this->assertSame( 'bar', $obj->attr( 'foo' )->getValue() );
    }

    public function testGetUndefinedAttributeDirectlyYieldsUndefined()
    {
        $obj = $this->object( 'bar' );

        $this->assertNull( $obj[ 'x' ] );
    }

    public function testSetAttributeValueDirectly()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );

        $obj[ 'name' ] = 'quux';

        $this->assertSame( 'quux', $obj->get( 'name' ));
    }

    public function testUnsetAttributeValueDirectly()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );

        $obj[ 'name' ] = 'quux';
        $this->assertSame( 'quux', $obj->get( 'name' ));

        unset( $obj[ 'name' ] );
        $this->assertNull( $obj->get( 'name' ));
    }

    public function testIssetAttributeValue()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );

        $this->assertTrue( isset( $obj[ 'name' ] ));
        $this->assertFalse( isset( $obj[ 'flix' ] ));
    }
/*
    public function testObjectToArrayConversion()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );

        $obj[ 'name' ] = 'quux';

        $expected = [
            ['name' => 'foo',  'value' => 'bar'],
            ['name' => 'name', 'value' => 'quux'],
        ];
        $this->assertEquals( $expected, $obj->toList() );
    }
//*/
    public function testJsonConversion()
    {
        $obj = $this->object( 'bar # test' );
        $this->define( $obj, 'comment', true, false );
        $this->define( $obj, 'source', false, true );
        $this->generate( $obj, 'last-modified', false );

        $obj->set( 'comment', ['Franz', 'Georg'] )
            ->set( 'source', 'phpunit' )
            ->set( 'last-modified', ( new \DateTime( 'now' ))->format( 'c' ))
        ;

        $data = [
            ["name" => "foo", "value" => "bar", "comment" => "test"],
            ["name" => "comment", "value" => "Franz"],
            ["name" => "comment", "value" => "Georg"],
            ["name" => "source", "value" => "phpunit"],
        ];
        $this->assertJsonStringEqualsJsonString( json_encode( $data ), json_encode( $obj ) );
    }

    public function testObjectStringification()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'comment', true, false );
        $this->define( $obj, 'source', false, true );
        $this->generate( $obj, 'last-modified', false );

        $obj[ 'comment' ] = ['fizz', 'buzz'];

        $text = $obj->toText();
        $lines = explode( \PHP_EOL, trim( $text )); // removing trailing LF

        $this->assertCount( 3, $lines );

        $this->assertStringStartsWith( 'foo:', $lines[ 0 ] );
        $this->assertStringEndsWith( 'bar', $lines[ 0 ] );

        $this->assertStringStartsWith( 'comment:', $lines[ 1 ] );
        $this->assertStringEndsWith( 'fizz', $lines[ 1 ] );

        $this->assertStringStartsWith( 'comment:', $lines[ 2 ] );
        $this->assertStringEndsWith( 'buzz', $lines[ 2 ] );
    }

    public function testCountDefinedAttributes()
    {
        $obj = $this->object( NULL );
        $this->define( $obj, 'comment', true, false );

        $obj[ 'comment' ] = ['fizz', 'buzz'];

        $this->assertCount( 1, $obj );
    }

    public function testIteratorInForeach()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'name', false, false );
        $this->define( $obj, 'comment', true, false );
        $this->define( $obj, 'source', false, true );
        $this->generate( $obj, 'last-modified', false );

        $obj[ 'comment' ] = ['fizz', 'buzz'];

        $this->assertGreaterThan( 0, count( $obj ));

        $names = $defined = [];
        foreach ( $obj as $name => $attr ) {
            $names[] = $name;
            $defined[] = $attr->isDefined();
        }
        $this->assertEquals( [ 'foo', 'name', 'comment', 'source', 'last-modified' ], $names );
        $this->assertEquals( [ true, false, true, false, false ], $defined );
    }

    public function testValidityStatus()
    {
        $obj = $this->object( 'bar' );
        $this->define( $obj, 'source', false, true );

        $this->assertFalse( $obj->isValid() );

        $obj[ 'source' ] = 'phpunit';

        $this->assertTrue( $obj->isValid() );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\IncompleteObjectException
     * @expectedExceptionMessage Required attribute source is not set.
     *
    public function testInvalidObjectThrowsExceptionWhenValidated()
    {
        $obj = $this->object( 'bar' );
        $obj->validate();
    }

    public function testValidObjectDoesNotThrowExceptionWhenValidated()
    {
        $obj = $this->object( 'bar' );
        $obj[ 'source' ] = 'phpunit';

        $this->assertTrue( $obj->validate() );
    }
//*/
}
