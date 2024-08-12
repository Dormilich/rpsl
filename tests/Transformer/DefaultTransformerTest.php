<?php

namespace Dormilich\RPSL\Tests\Transformer;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Transformer\DefaultTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultTransformer::class)]
#[UsesClass(Value::class)]
class DefaultTransformerTest extends TestCase
{
    private function transformer(): DefaultTransformer
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        $transformer = new DefaultTransformer();

        return $transformer->setAttribute($attribute);
    }

    #[Test, TestDox('convert empty value')]
    #[TestWith([null])]
    #[TestWith([''])]
    public function empty_values(mixed $value)
    {
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isEmpty(), 'value is not empty');
    }

    #[Test, TestDox('convert from string')]
    public function string_value()
    {
        $data = $this->transformer()->serialize('foo bar');

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('foo bar', $data->getValue());
    }

    #[Test, TestDox('convert from integer')]
    #[TestWith([0, '0'])]
    #[TestWith([18159228951, '18159228951'])]
    public function integer_value(mixed $value, string $expected)
    {
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame($expected, $data->getValue());
    }

    #[Test, TestDox('convert from float')]
    #[TestWith([0.0, '0.0'])]
    #[TestWith([3.14, '3.14'])]
    public function float_value(mixed $value, string $expected)
    {
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame($expected, $data->getValue());
    }

    #[Test, TestDox('convert from boolean')]
    #[TestWith([false, 'false'])]
    #[TestWith([true, 'true'])]
    public function boolean_value(mixed $value, string $expected)
    {
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame($expected, $data->getValue());
    }

    #[Test, TestDox('convert from stringable')]
    public function stringable_value()
    {
        $value = $this->createConfiguredStub(\Stringable::class, [
            '__toString' => 'foo bar',
        ]);
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('foo bar', $data->getValue());
    }

    #[Test, TestDox('convert from RPSL object')]
    public function rpsl_object()
    {
        $value = $this->createConfiguredStub(ObjectInterface::class, [
            'getHandle' => 'TEST-RIPE',
            'getType' => 'person',
        ]);
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('TEST-RIPE', $data->getValue());
        $this->assertSame('person', $data->getType());
    }

    #[Test, TestDox('convert from attribute')]
    public function attribute_value()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'foo',
        ]);
        $value = new Value($attribute, 'TEST-RIPE', 'my friend', 'person');
        $data = $this->transformer()->serialize($value);

        $this->assertTrue($data->isDefined(), 'value is not defined');
        $this->assertSame('TEST-RIPE', $data->getValue());
        $this->assertSame('person', $data->getType());
        $this->assertSame('my friend', $data->getComment());
        $this->assertSame('test', $data->getName());
    }

    #[Test, TestDox('unsupported input data type fails')]
    public function invalid_type()
    {
        $this->expectException(TransformerException::class);
        $this->expectExceptionCode(TransformerException::INVALID_DATA_TYPE);
        $this->expectExceptionMessage('Value of type [stdClass] could not be stored in the "test" attribute.');

        $this->transformer()->serialize(new \stdClass());
    }

    #[Test, TestDox('retrieve value as string')]
    public function get_value()
    {
        $transformer = $this->transformer();
        $value = $transformer->serialize(42);
        $data = $transformer->unserialize($value);

        $this->assertSame('42', $data);
    }

    #[Test, TestDox('retrieve empty value as NULL')]
    public function get_empty_value()
    {
        $transformer = $this->transformer();
        $value = $transformer->serialize('');
        $data = $transformer->unserialize($value);

        $this->assertNull($data);
    }
}
