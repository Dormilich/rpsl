<?php declare(strict_types=1);

namespace Dormilich\RPSL\Tests\Attribute;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(Value::class)]
class ValueTest extends TestCase
{
    #[Test, TestDox('create from value without comment')]
    public function create_without_comment()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        $value = new Value($attribute, 'value without a comment');

        $this->assertTrue($value->isDefined());
        $this->assertSame('test', $value->getName());
        $this->assertSame('value without a comment', $value->getValue());
        $this->assertNull($value->getType());
        $this->assertNull($value->getComment());
        $this->assertSame('value without a comment', (string) $value);
    }

    #[Test, TestDox('create from value with inline comment')]
    public function create_with_comment()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        $value = new Value($attribute, 'value # with a comment');

        $this->assertTrue($value->isDefined());
        $this->assertSame('test', $value->getName());
        $this->assertSame('value', $value->getValue());
        $this->assertNull($value->getType());
        $this->assertSame('with a comment', $value->getComment());
        $this->assertSame('value # with a comment', (string) $value);
    }

    #[Test, TestDox('set properties separately')]
    public function create_from_json()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        $value = new Value($attribute, 'TEST-RIPE', 'my best buddy', 'person');

        $this->assertTrue($value->isDefined());
        $this->assertSame('test', $value->getName());
        $this->assertSame('TEST-RIPE', $value->getValue());
        $this->assertSame('person', $value->getType());
        $this->assertSame('my best buddy', $value->getComment());
        $this->assertSame('TEST-RIPE # my best buddy', (string) $value);
    }

    #[Test, TestDox('inline comment without value becomes empty')]
    public function comment_without_value()
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        $value = new Value($attribute, '### I am ASCII art ###');

        $this->assertFalse($value->isDefined());
    }
}
