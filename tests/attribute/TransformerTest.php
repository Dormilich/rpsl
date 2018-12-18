<?php

use Dormilich\RPSL\Attribute;
use Dormilich\RPSL\AttributeValue;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Transformers as TF;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    private function getStringObject( $string )
    {
        $mock = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ '__toString' ] )
            ->getMock();

        $mock->method( '__toString' )->willReturn( $string );

        return $mock;
    }

    public function inputProvider()
    {
        $str = $this->getStringObject( 'test' );
        $scl = new \stdClass;
        $obj = $this->createMock( ObjectInterface::class );

        return [
            [ 3.14, '3.14'],
            [ TRUE, 'true' ],
            [ FALSE, 'false' ],
            [ 'test', 'test' ],
            [ $str, 'test' ],
            [ NULL, NULL ],
            [ $scl, $scl ],
            [ $obj, $obj ],
        ];
    }

    /**
     * @dataProvider inputProvider
     */
    public function testDefaultTransformer( $data, $expected )
    {
        $tf = new TF\DefaultTransformer;

        $value = $tf->transform( $data );

        $this->assertSame( $expected, $value );
    }

    public function testDefaultReverseTransformerOnEmpty()
    {
        $av = new AttributeValue( '' );
        $tf = new TF\DefaultTransformer;

        $data = $tf->reverseTransform( $av );

        $this->assertNull( $data );
    }

    public function testDefaultReverseTransformerOnString()
    {
        $av = new AttributeValue( 'foo # bar' );
        $tf = new TF\DefaultTransformer;

        $data = $tf->reverseTransform( $av );

        $this->assertSame( 'foo', $data );
    }

    public function testDefaultReverseTransformerOnObject()
    {
        $obj = $this->createMock( ObjectInterface::class );
        $obj->method( 'getHandle' )->willReturn( 'phpunit' );
        $obj->method( 'getName' )->willReturn( 'exception' );

        $av = new AttributeValue( $obj );
        $tf = new TF\DefaultTransformer;

        $data = $tf->reverseTransform( $av );

        $this->assertInstanceOf( 'Exception', $data );
        $this->assertSame( 'phpunit', $data->getMessage() );
    }

    public function testDatetimeTransformerOnString()
    {
        $tf = new TF\DatetimeTransformer;

        $date = '1969-07-16T13:32:00Z';
        $value = $tf->transform( $date );

        $this->assertSame( '1969-07-16T13:32:00+00:00', $value );
    }

    public function testDatetimeTransformerOnObject()
    {
        $tf = new TF\DatetimeTransformer;

        $date = new \DateTime( '1969-07-16T13:32:00Z' );
        $value = $tf->transform( $date );

        $this->assertSame( '1969-07-16T13:32:00+00:00', $value );
    }

    public function testDatetimeEmptyReverseTransformer()
    {
        $tf = new TF\DatetimeTransformer;

        $av = new AttributeValue( '' );
        $value = $tf->reverseTransform( $av );

        $this->assertNull( $value );
    }

    public function testDatetimeDefaultReverseTransformer()
    {
        $tf = new TF\DatetimeTransformer;

        $av = new AttributeValue( '1969-07-16T13:32:00Z' );
        $value = $tf->reverseTransform( $av );

        $this->assertInstanceOf( 'DateTimeImmutable', $value );
        $this->assertSame( '1969-07-16T13:32:00+00:00', $value->format( DATE_W3C ) );
    }

    public function testDatetimeConfiguredReverseTransformer()
    {
        $tz = new \DateTimeZone( 'Europe/Volgograd' );
        $conf = new \DateTime( 'now', $tz );
        $tf = new TF\DatetimeTransformer( $conf );

        $av = new AttributeValue( '1969-07-16T13:32:00Z' );
        $value = $tf->reverseTransform( $av );

        $this->assertInstanceOf( 'DateTime', $value );
        $this->assertSame( '1969-07-16T17:32:00+04:00', $value->format( DATE_W3C ) );
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testDatetimeTransformerWithInvalidValueEmitsNotice()
    {
        $tf = new TF\DatetimeTransformer;
        $tf->transform( 'foo' );
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Notice
     */
    public function testDatetimeReverseTransformerWithInvalidValueEmitsNotice()
    {
        $tf = new TF\DatetimeTransformer;
        $av = new AttributeValue( 'foo' );
        $tf->reverseTransform( $av );
    }

    public function testDatetimeTransformerIgnoresInvalidValue()
    {
        $tf = new TF\DatetimeTransformer;
        $av = new AttributeValue( 'bar' );

        $value = @$tf->transform( 'foo' );
        $this->assertSame( 'foo', $value );

        $data = @$tf->reverseTransform( $av );
        $this->assertSame( 'bar', $data );
    }

    public function testCallbackTransformer()
    {
        $mock = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ 'input', 'output' ] )
            ->getMock();
        $mock->expects( $this->never() )->method( 'output' );
        $mock->expects( $this->once() )
            ->method( 'input' )
            ->with( $this->identicalTo( 'foo' ) );

        $tf = new TF\CallbackTransformer( [$mock, 'input'], [$mock, 'output'] );
        $tf->transform( 'foo' );
    }

    public function testCallbackReverseTransformer()
    {
        $mock = $this->getMockBuilder( 'stdClass' )
            ->setMethods( [ 'input', 'output' ] )
            ->getMock();
        $mock->expects( $this->never() )->method( 'input' );
        $mock->expects( $this->once() )
            ->method( 'output' )
            ->with( $this->identicalTo( 'foo' ) );

        $tf = new TF\CallbackTransformer( [$mock, 'input'], [$mock, 'output'] );
        $av = new AttributeValue( 'foo # bar' );
        $tf->reverseTransform( $av );
    }
}
