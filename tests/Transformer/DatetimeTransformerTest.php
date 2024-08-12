<?php

namespace Dormilich\RPSL\Tests\Transformer;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;
use Dormilich\RPSL\Transformer\DatetimeTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatetimeTransformer::class)]
#[UsesClass(Value::class)]
class DatetimeTransformerTest extends TestCase
{
    private function withAttribute(DatetimeTransformer $transformer): DatetimeTransformer
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        return $transformer->setAttribute($attribute);
    }

    #[Test, TestDox('convert timestamp with timezone')]
    public function full_iso_format()
    {
        $setup = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $transformer = new DatetimeTransformer($setup);
        $data = $this->withAttribute($transformer)->serialize('2020-02-02T12:34:56Z');

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('2020-02-02T12:34:56+00:00', $data->getValue());
    }

    #[Test, TestDox('convert timestamp without timezone')]
    public function local_iso_format()
    {
        $setup = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $transformer = new DatetimeTransformer($setup);
        $data = $this->withAttribute($transformer)->serialize('2020-02-02T12:34:56');

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('2020-02-02T12:34:56+01:00', $data->getValue());
    }

    #[Test, TestDox('convert timestamp in local format')]
    public function local_format()
    {
        $setup = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $transformer = new DatetimeTransformer($setup);
        $data = $this->withAttribute($transformer)->serialize('2020-02-02 12:34:56');

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('2020-02-02T12:34:56+01:00', $data->getValue());
    }

    #[Test, TestDox('convert from date object')]
    public function date_object()
    {
        $value = new \DateTime('2020-02-02T12:34:56Z');
        $transformer = new DatetimeTransformer();
        $data = $this->withAttribute($transformer)->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('2020-02-02T12:34:56+00:00', $data->getValue());
    }

    #[Test, TestDox('convert from attribute value')]
    public function attribute_value()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);
        $value = new Value($attribute, '2020-02-02T12:34:56Z');
        $transformer = new DatetimeTransformer();
        $data = $transformer->setAttribute($attribute)->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('2020-02-02T12:34:56+00:00', $data->getValue());
    }

    #[Test, TestDox('convert empty value')]
    #[TestWith([null])]
    #[TestWith([''])]
    public function empty_value(mixed $value)
    {
        $transformer = new DatetimeTransformer();
        $data = $this->withAttribute($transformer)->serialize($value);

        $this->assertTrue($data->isEmpty(), 'value is not empty');
    }

    #[Test, TestDox('convert invalid data type fails')]
    public function invalid_type()
    {
        $this->expectException(TransformerException::class);
        $this->expectExceptionCode(TransformerException::INVALID_DATA_TYPE);
        $this->expectExceptionMessage('Value of type [stdClass] could not be stored in the "test" attribute.');

        $transformer = new DatetimeTransformer();
        $this->withAttribute($transformer)->serialize(new \stdClass());
    }

    #[Test, TestDox('convert invalid value fails')]
    public function invalid_value()
    {
        $this->expectException(TransformerException::class);
        $this->expectExceptionCode(TransformerException::INVALID_VALUE);
        $this->expectExceptionMessage('Value ["foo"] could not be stored in the "test" attribute.');

        $transformer = new DatetimeTransformer();
        $this->withAttribute($transformer)->serialize('foo');
    }

    #[Test, TestDox('retrieve DateTime object')]
    public function get_mutable_date()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);
        $value = new Value($attribute, '2020-02-02T12:34:56Z');
        $setup = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $transformer = new DatetimeTransformer($setup);
        $data = $transformer->setAttribute($attribute)->unserialize($value);

        $this->assertInstanceOf(\DateTime::class, $data);
        $this->assertSame('Europe/Berlin', $data->getTimezone()->getName());
        $this->assertSame('2020-02-02', $data->format('Y-m-d'));
        $this->assertSame('13:34:56', $data->format('H:i:s'));
    }

    #[Test, TestDox('retrieve DateTimeImmutable object')]
    public function get_immutable_date()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);
        $value = new Value($attribute, '2020-02-02T12:34:56Z');
        $setup = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Berlin'));
        $transformer = new DatetimeTransformer($setup);
        $data = $transformer->setAttribute($attribute)->unserialize($value);

        $this->assertInstanceOf(\DateTimeImmutable::class, $data);
        $this->assertSame('Europe/Berlin', $data->getTimezone()->getName());
        $this->assertSame('2020-02-02', $data->format('Y-m-d'));
        $this->assertSame('13:34:56', $data->format('H:i:s'));
    }
}
