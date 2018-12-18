<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\AttributeInterface as Attr;
use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\NamespaceAware;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Transformers\TransformerInterface;
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

    private function getStringObject( $string )
    {
        $mock = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ '__toString' ] )
            ->getMock();

        $mock->method( '__toString' )->willReturn( $string );

        return $mock;
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
            [Attr::MANDATORY, Attr::SINGLE,   true, false],
            [Attr::OPTIONAL, Attr::MULTIPLE, false, true ],
        ];
    }

    /**
     * @dataProvider constructorPropertyProvider
     */
    public function testSetCorrectProperties( $required, $multiple, $expect_required, $expect_multiple )
    {
        $attr = new Attribute( 'foo', $required, $multiple );

        $this->assertSame( $expect_required, $attr->isMandatory() );
        $this->assertSame( $expect_multiple, $attr->isMultiple() );
        $this->assertSame( ! $expect_required, $attr->isOptional() );
        $this->assertSame( ! $expect_multiple, $attr->isSingle() );
    }

    public function testSetupNamespacedAttribute()
    {
        $obj = $this->createMock( NamespaceAware::class );
        $obj->method( 'getNamespace' )->willReturn( 'Ripe' );

        $attr = new Attribute( 'test', false, false, $obj );
        $this->assertSame( 'Ripe', $attr->getNamespace() );
    }

    // value

    public function testAttributeIsEmptyByDefault()
    {
        $attr = new Attribute( 'foo', true, true );

        $this->assertFalse( $attr->isDefined() );
        $this->assertTrue( $attr->isEmpty() );
        $this->assertNull( $attr->getValue() );
    }

    public function testAttributeWithValueIsDefined()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( 'x' );

        $this->assertTrue( $attr->isDefined() );
        $this->assertFalse( $attr->isEmpty() );
        $this->assertNotNull( $attr->getValue() );
    }

    public function testAttributeWithEmptyStringIsNotDefined()
    {
        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( '' );

        $this->assertFalse( $attr->isDefined() );
        $this->assertTrue( $attr->isEmpty() );
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
        $test = $this->getStringObject( 'test' );
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

    private function getNamespaceValue( AttributeValue $value )
    {
        $rc = new \ReflectionClass( AttributeValue::class );
        $rp = $rc->getProperty( 'namespace' );
        $rp->setAccessible( true );

        return $rp->getValue( $value );
    }

    public function testAttributePassesNamespaceToValue()
    {
        $obj = $this->createMock( NamespaceAware::class );
        $obj->method( 'getNamespace' )->willReturn( 'Ripe' );

        $attr = new Attribute( 'foo', false, false, $obj );
        $attr->setValue( 'phpunit' );

        $value = $attr->current();

        $this->assertInstanceOf( AttributeValue::class, $value );
        $this->assertSame( 'Ripe', $this->getNamespaceValue( $value ) );
    }

    public function testAttributeNamespaceResolutionPrefersInput()
    {
        $obj = $this->createMock( NamespaceAware::class );
        $obj->method( 'getNamespace' )->willReturn( 'Ripe' );

        $data = $this->createMock( [ ObjectInterface::class, NamespaceAware::class ] );
        $data->method( 'getHandle' )->willReturn( 'phpunit' );
        $data->method( 'getName' )->willReturn( 'role' );
        $data->method( 'getNamespace' )->willReturn( 'Apnic' );

        $attr = new Attribute( 'foo', false, false, $obj );
        $attr->setValue( $data );

        $value = $attr->current();

        $this->assertInstanceOf( AttributeValue::class, $value );
        $this->assertSame( 'Apnic', $this->getNamespaceValue( $value ) );
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

    public function testAttributeAllowsAttributeValue()
    {
        $val = new AttributeValue( 'foo#bar' );

        $attr = new Attribute( 'foo', Attr::MANDATORY, Attr::SINGLE );
        $attr->setValue( $val );

        $this->assertSame( 'foo # bar', $attr->getValue() );
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

    public function testTransformerReceivesInput()
    {
        $tf = $this->createMock( TransformerInterface::class );
        $tf->expects( $this->once() )
            ->method( 'transform' )
            ->with( $this->isInstanceOf( 'stdClass' ) )
            ->will( $this->returnCallback( 'strtoupper' ) );

        $obj = $this->getStringObject( 'phpunit' );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( $tf );
        $attr->setValue( $obj );

        $this->assertSame( 'PHPUNIT', $attr->getValue() );
    }

    // An already valid value must not be invalidated by a transformer. 
    // References (as read from RPSL objects) are considered already valid.
    public function testSetRpslObjectDoesNotRunTransformer()
    {
        $tf = $this->createMock( TransformerInterface::class );
        $tf->expects( $this->never() )->method( 'transform' );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( $tf );

        $obj = $this->rpsl( 'ABC' );
        $attr->setValue( $obj );
    }

    // An `AttributeValue` object is returned when iterating over an `Attribute` object
    public function testSetValueObjectDoesNotRunTransformer()
    {
        $tf = $this->createMock( TransformerInterface::class );
        $tf->expects( $this->never() )->method( 'transform' );

        $obj = new AttributeValue( 'phpunit' );
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( $tf );
        $attr->setValue( $obj );
    }

    // input validation

    public function inputValueProvider()
    {
        $val = new AttributeValue( 'phpunit' );

        return [
            [ 'phpunit' ],                  // string
            [ $this->rpsl( 'phpunit' ) ],   // RPSL object
            [ $val ],                       // attribute value
        ];
    }

    /**
     * @dataProvider inputValueProvider
     */
    public function testValidatorIsAlwaysExecuted( $input )
    {
        $vd = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ '__invoke' ] )
            ->getMock();
        $vd->expects( $this->once() )
            ->method( '__invoke' )
            ->with( $this->identicalTo( 'phpunit' ))
            ->willReturn( true );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->test( $vd );
        $attr->setValue( $input );
    }

    public function testValidatorIgnoresComment()
    {
        $vd = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ '__invoke' ] )
            ->getMock();
        $vd->expects( $this->once() )
            ->method( '__invoke' )
            ->with( $this->identicalTo( 'ABC' ))
            ->willReturn( true );

        $obj = new AttributeValue( 'ABC # comment' );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->test( $vd );
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
        $this->assertEquals( [ 'phpunit' ], $array1 );
    }

    public function testAttributeArrayAccessIsset()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( [ 'foo', 'bar', 'baz' ] );

        $this->assertTrue( isset( $attr[ 0 ] ) );
        $this->assertTrue( isset( $attr[ -1 ] ) );
        $this->assertFalse( isset( $attr[ 5 ] ) );
    }

    public function testAttributeArrayAccessGet()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( [ 'foo', 'bar', 'baz' ] );

        $this->assertSame( 'foo', $attr[ 0 ] );
        $this->assertSame( 'baz', $attr[ -1 ] );
    }

    public function testAttributeArrayAccessSet()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( 'foo' );
        // append
        $attr[] = 'bar';
        $this->assertCount( 2, $attr );
        $this->assertSame( [ 'foo', 'bar' ], $attr->getValues() );
        // edit by reverse index
        $attr[ -1 ] = 'baz';
        $this->assertCount( 2, $attr );
        $this->assertSame( [ 'foo', 'baz' ], $attr->getValues() );
        // ignore undefined index
        $attr[ 3 ] = 'quux';
        $this->assertSame( [ 'foo', 'baz' ], $attr->getValues() );
    }

    public function testAttributeArrayAccessUnset()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( [ 'foo', 'bar', 'baz' ] );
        unset( $attr[ 1 ] );

        $this->assertSame( ['foo', 'baz'], $attr->getValue() );
    }

    public function testValueAccessCallsTransformer()
    {
        $tf = $this->createMock( TransformerInterface::class );
        $tf->method( 'transform' )->will( $this->returnArgument( 0 ) );
        $tf->expects( $this->once() )
            ->method( 'reverseTransform' )
            ->with( $this->isInstanceOf( AttributeValue::class ) )
            ->will( $this->returnCallback( 'strval' ) );

        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );
        $attr->apply( $tf );
        $attr->setValue( 'phpunit' );

        $this->assertSame( 'phpunit', $attr[ 0 ] );
    }

    public function testUndefinedValueOffsetReturnsNothing()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::SINGLE );

        $this->assertNull( $attr[ 0 ] );
    }

    public function testRemoveSingleAttributeValue()
    {
        $attr = new Attribute( 'test', Attr::MANDATORY, Attr::MULTIPLE );
        $attr->setValue( [ 'foo', 'bar' ] );

        unset( $attr[ 0 ] );
        $this->assertSame( [ 'bar' ], $attr->getValues() );
    }
}
