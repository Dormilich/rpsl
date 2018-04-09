<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\AttributeInterface as Attr;
use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\ObjectInterface;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    private function rpsl( $handle )
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getName' )->willReturn( 'test' );
        $obj->method( 'getHandle' )->willReturn( $handle );

        return $obj;
    }

    // setup - name

    public function testAttributeHasCorrectName()
    {
        $attr = new Attribute( 'foo', true, true );
        $this->assertSame( 'foo', $attr->getName() );

        $attr = new Attribute( 1.8, true, true );
        $this->assertSame( '1.8', $attr->getName() );
    }

    // setup - properties

    public function constructorPropertyProvider()
    {
        return [
            [true,  true,  true,  true ], 
            [true,  false, true,  false], 
            [false, true,  false, true ], 
            [false, false, false, false], 
            [0,     1,     false, true ], 
            ['yes', 'no',  true,  false], 
            ['x',   NULL,  false, false],
            [Attr::MANDATORY, Attr::SINGLE,   true,  false],
            [Attr::OPTIONAL, Attr::MULTIPLE, false, true ],
        ];
    }

    /**
     * @dataProvider constructorPropertyProvider
     */
    public function testSetCorrectProperties( $required, $multiple, $expect_required, $expect_multiple )
    {
        $attr = new Attribute( 'foo', $required, $multiple );

        $this->assertSame( $expect_required, $attr->isRequired() );
        $this->assertSame( $expect_multiple, $attr->isMultiple() );
    }

    // value

    public function testAttributeIsEmptyByDefault()
    {
        $attr = new Attribute( 'foo', true, true );
        $this->assertFalse( $attr->isDefined() );
        $this->assertNull( $attr->getValue() );
    }

    public function testAttributeWithValueIsDefined()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( 'x' );

        $this->assertTrue( $attr->isDefined() );
        $this->assertNotNull( $attr->getValue() );
    }

    public function testAttributeWithEmptyStringIsNotDefined()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( '' );

        $this->assertFalse( $attr->isDefined() );
    }

    public function testInputIsConvertedToStrings()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        // integer
        $attr->setValue( 1 );
        $this->assertSame( '1', $attr->getValue() );
        // float
        $attr->setValue( 2.718 );
        $this->assertSame( '2.718', $attr->getValue() );
        // string
        $attr->setValue( 'bar' );
        $this->assertSame( 'bar', $attr->getValue() );
        // stringifiable object
        $test = $this->createMock( 'Exception' );
        $test->method( '__toString' )->willReturn( 'test' );
        $attr->setValue( $test );
        $this->assertSame( 'test', $attr->getValue() );
        // boolean
        // I am not aware that RPSL uses booleans anywhere…
    }

    public function testSingleAttributeOnlyHasOneValue()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );

        $this->assertNull( $attr->getValue() );

        $attr->addValue( 'fizz' );
        $this->assertSame( 'fizz', $attr->getValue() );

        $attr->addValue( 'buzz' );
        $this->assertSame( 'buzz', $attr->getValue() );
    }

    public function testMultipleAttributeReturnsList()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );

        $this->assertNull( $attr->getValue() );

        $attr->addValue( 'fizz' );
        $this->assertSame( ['fizz'], $attr->getValue() );

        $attr->addValue( 'buzz' );
        $this->assertSame( ['fizz', 'buzz'], $attr->getValue() );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidDataTypeException
     */
    public function testSingleAttributeDoesNotAllowArrayInput()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( ['fizz', 'buzz'] );
    }

    public function testSetValueResetsAttributeValue()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );

        $attr->setValue( 'fizz' );
        $this->assertSame( ['fizz'], $attr->getValue() );

        $attr->setValue( 'buzz' );
        $this->assertSame( ['buzz'], $attr->getValue() );
    }

    public function testNullResetsAttributeValue()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );

        $attr->setValue( 'foo' );
        $this->assertTrue( $attr->isDefined() );

        $attr->setValue( NULL );
        $this->assertFalse( $attr->isDefined() );
    }

    // input types

    public function testAttributeAllowsRpslObject()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $obj = $this->rpsl( uniqid() );

        $attr->setValue( $obj );
        $this->assertSame( $obj->getHandle(), $attr->getValue() );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidDataTypeException
     * @expectedExceptionMessage The [foo] attribute does not allow the resource data type.
     */
    public function testAttributeDoesNotAcceptResource()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( tmpfile() );
    }

    /**
     * @expectedException \Dormilich\RPSL\Exceptions\InvalidDataTypeException
     * @expectedExceptionMessage The [foo] attribute does not allow the stdClass data type.
     */
    public function testAttributeDoesNotAcceptArbitraryObject()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( new stdClass );
    }

    public function testMultipleAttributeAllowsStringArray()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );

        $attr->setValue( ['fizz', 'buzz'] );
        $this->assertSame( ['fizz', 'buzz'], $attr->getValue() );
    }

    public function testMultipleAttributeAllowsAttribute()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );
        $src = new Attribute( 'bar', Attr::MANDATORY, Attr::MULTIPLE );
        $src->setValue( ['fizz', 'buzz'] );

        $attr->setValue( $src );
        $this->assertSame( ['fizz', 'buzz'], $attr->getValue() );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidDataTypeException
     */
    public function testMultipleAttributeDoesNotAllowNonScalarArray()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( [NULL] );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidDataTypeException
     */
    public function testMultipleAttributeDoesNotAllowNestedArray()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( ['bar', [1,2,3]] );
    }

    public function testMultipleAttributeIgnoresArrayKeys()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );

        $attr->setValue( ['fizz' => 'buzz'] );
        $this->assertSame( ['buzz'], $attr->getValue() );
    }

    public function testMultipleAttributeSplitsMultilineText()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::MULTIPLE );

        $value = <<<TXT
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor
incidunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
exercitation ullamco laboris nisi ut aliquid ex ea commodi consequat. Quis aute
iure reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.

Excepteur sint obcaecat cupiditat non proident, sunt in culpa qui officia deserunt
mollit anim id est laborum.
TXT;
        $attr->setValue( $value );
        $this->assertCount( 7, $attr );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidDataTypeException
     * @expectedExceptionMessage The [foo] attribute does not allow the array data type.
     */
    public function testSingleAttributeDoesNotAllowMultilineText()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );

        $value = <<<TXT
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor
incidunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
exercitation ullamco laboris nisi ut aliquid ex ea commodi consequat. Quis aute
iure reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.

Excepteur sint obcaecat cupiditat non proident, sunt in culpa qui officia deserunt
mollit anim id est laborum.
TXT;
        $attr->setValue( $value );
    }

    // input transformation

    public function testSetStringValueRunsTransformer()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( 'strtoupper' );
        $attr->setValue( 'x' );

        $this->assertSame( 'X', $attr->getValue() );
    }

    public function testTransformerReceivesStringInput()
    {
        $obj = $this->createMock( 'Exception' );
        $obj->method( '__toString' )->willReturn( 'phpunit' );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );

        $attr->apply( function ( $input ) {
            $this->assertInstanceOf( 'Exception', $input );
            return strtoupper( $input );
        } );

        $attr->setValue( $obj );

        $this->assertSame( 'PHPUNIT', $attr->getValue() );
    }

    public function testSetRpslObjectDoesNotRunTransformer()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( 'strtolower' );
        // an already valid value must not be invalidated by a transformer 
        // references ( as read from RPSL object ) are considered already valid
        $obj = $this->rpsl( 'ABC' );
        $attr->setValue( $obj );

        $this->assertNotEquals( 'abc', $attr->getValue() );
    }

    public function testSetValueObjectDoesNotRunTransformer()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( 'strtolower' );

        // getting your hands on an AttributeValue is a bit tricky
        $src = new Attribute( 'source', Attr::MANDATORY, Attr::SINGLE );
        
        $obj = $src->setValue( 'XXX' )->current();

        $this->assertInternalType( 'object', $obj ); // prove that we have an AttributeValue object
        $attr->setValue( $obj );
        $this->assertNotEquals( 'xxx', $attr->getValue() );
    }

    // input validation

    public function inputValueProvider()
    {
        // @see testSetValueObjectDoesNotRunTransformer
        $src = new Attribute( 'source', Attr::MANDATORY, Attr::SINGLE );
        $src->setValue( 'test' );

        return [
            [ 'test' ],
            [ $this->rpsl( 'test' ) ],
            [ $src->current() ],
        ];
    }

    /**
     * @dataProvider inputValueProvider
     */
    public function testValidatorIsAlwaysExecuted( $input )
    {
        $obj = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ 'check' ] )
            ->getMock();
        $obj->expects( $this->once() )
            ->method( 'check' )
            ->with( $this->identicalTo( 'test' ))
            ->willReturn( true )
        ;

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );

        $attr->test( [$obj, 'check'] );
        $attr->setValue( $input );
    }

    public function testValidatorIgnoresComment()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $obj = new AttributeValue( 'ABC # comment' );

        $attr->test( function ( $input ) {
            $this->assertSame( 'ABC', $input );
            return true;
        } );

        $attr->setValue( $obj );
    }

    /**
     * @expectedException Dormilich\RPSL\Exceptions\InvalidValueException
     * @expectedExceptionMessage Value "1" is not allowed for the [test] attribute.
     */
    public function testFailedValidationThrowsException()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->test( 'is_null' );
        $attr->setValue( 1 );
    }

    // interface implementation

    public function testSingleAttributeValueCount()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );

        $this->assertCount( 0, $attr );

        $attr->addValue( 1 );
        $this->assertCount( 1, $attr );

        $attr->addValue( 2 );
        $this->assertCount( 1, $attr );
    }

    public function testMultipleAttributeValueCount()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );

        $this->assertCount( 0, $attr );

        $attr->addValue( 1 );
        $this->assertCount( 1, $attr );

        $attr->addValue( 2 );
        $this->assertCount( 2, $attr );
    }

    public function testJsonConversion()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( ['fizz', 'buzz # test'] );

        $expected = [
            ['name' => 'test', 'value' => 'fizz'],
            ['name' => 'test', 'value' => 'buzz', 'comment' => 'test'],
        ];
        $this->assertJsonStringEqualsJsonString( json_encode( $expected ), json_encode( $attr ));
    }

    public function testAttributeIsIterable()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );

        $array0 = iterator_to_array( $attr );
        $this->assertEquals( [], $array0 );

        $attr->setValue( 'phpunit' );
        $array1 = iterator_to_array( $attr );
        $this->assertEquals( ['phpunit'], $array1 );
    }

    public function testGetSingleAtributeValueWithoutComment()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( 'foo # bar' );

        $this->assertSame( 'foo', $attr[ 0 ] );
    }

    public function testGetSingleAtributeValueObject()
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getHandle' )->willReturn( 'phpunit' );
        $obj->method( 'getName' )->willReturn( 'exception' );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( $obj );

        $this->assertInstanceOf( 'Exception', $attr[ 0 ] );
    }

    public function testUndefinedValueOffsetReturnsNothing()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );

        $this->assertNull( $attr[ 0 ] );
    }

    public function testSetSingleAtributeValue()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->apply( 'strtoupper' )->setValue( ['foo', 'bar'] );

        $this->assertSame( 'BAR', $attr[ 1 ] );

        $attr[ 1 ] = 'phpunit # test';
        $this->assertSame( 'PHPUNIT', $attr[ 1 ] );
        $this->assertSame( ['FOO', 'PHPUNIT # TEST'], $attr->getValue() );
    }

    public function testRemoveSingleAttributeValue()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( ['foo', 'bar'] );

        unset( $attr[ 0 ] );
        $this->assertSame( ['bar'], $attr->getValue() );
    }
}
