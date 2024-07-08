<?php

namespace Dormilich\RPSL\Tests\Attribute;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Container;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Attribute\Presence;
use Dormilich\RPSL\Attribute\Repeat;
use Dormilich\RPSL\Transformer\DefaultTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Attribute::class)]
#[UsesClass(Container::class), UsesClass(DefaultTransformer::class), UsesClass(Value::class)]
class AttributeTest extends TestCase
{
    #[Test, TestDox('set up single attribute')]
    public function setup_single()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::single);

        $this->assertSame('test', $attribute->getName());
        $this->assertTrue($attribute->isMandatory(), 'attribute is not mandatory');
        $this->assertFalse($attribute->isOptional(), 'attribute is optional');
        $this->assertFalse($attribute->isGenerated(), 'attribute is generated');
        $this->assertTrue($attribute->isSingle(), 'attribute is not single');
        $this->assertFalse($attribute->isMultiple(), 'attribute is multiple');
    }

    #[Test, TestDox('set up multiple attribute')]
    public function setup_multiple()
    {
        $attribute = new Attribute('test', Presence::optional, Repeat::multiple);

        $this->assertSame('test', $attribute->getName());
        $this->assertFalse($attribute->isMandatory(), 'attribute is mandatory');
        $this->assertTrue($attribute->isOptional(), 'attribute is not optional');
        $this->assertFalse($attribute->isGenerated(), 'attribute is generated');
        $this->assertFalse($attribute->isSingle(), 'attribute is single');
        $this->assertTrue($attribute->isMultiple(), 'attribute is not multiple');
    }

    #[Test, TestDox('set up generated attribute')]
    public function setup_generated()
    {
        $attribute = new Attribute('test', Presence::generated, Repeat::single);

        $this->assertSame('test', $attribute->getName());
        $this->assertFalse($attribute->isMandatory(), 'attribute is mandatory');
        $this->assertTrue($attribute->isOptional(), 'attribute is not optional');
        $this->assertTrue($attribute->isGenerated(), 'attribute is not generated');
        $this->assertTrue($attribute->isSingle(), 'attribute is not single');
        $this->assertFalse($attribute->isMultiple(), 'attribute is multiple');
    }

    #[Test, TestDox('set up generated attribute')]
    public function setup_primary_key()
    {
        $attribute = new Attribute('test', Presence::primary_key, Repeat::single);

        $this->assertSame('test', $attribute->getName());
        $this->assertTrue($attribute->isMandatory(), 'attribute is not mandatory');
        $this->assertFalse($attribute->isOptional(), 'attribute is optional');
        $this->assertFalse($attribute->isGenerated(), 'attribute is generated');
        $this->assertTrue($attribute->isSingle(), 'attribute is not single');
        $this->assertFalse($attribute->isMultiple(), 'attribute is multiple');
    }

    #[Test, TestDox('new attribute is empty')]
    public function create_empty()
    {
        $attribute = new Attribute('test', Presence::optional, Repeat::multiple);

        $this->assertTrue($attribute->isEmpty(), 'attribute is not empty');
        $this->assertFalse($attribute->isDefined(), 'attribute is defined');
        $this->assertCount(0, $attribute);
        $this->assertNull($attribute->getValue());
    }

    #[Test, TestDox('set string value')]
    public function set_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);

        $attribute->setValue('foo # bar');

        $this->assertFalse($attribute->isEmpty(), 'attribute is empty');
        $this->assertTrue($attribute->isDefined(), 'attribute is not defined');
        $this->assertCount(1, $attribute);
        $this->assertSame(['foo'], $attribute->getValue());
    }

    #[Test, TestDox('set array values')]
    public function set_array_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);

        $attribute->setValue(['foo', 'bar']);

        $this->assertCount(2, $attribute);
        $this->assertSame(['foo', 'bar'], $attribute->getValue());
    }

    #[Test, TestDox('add text block')]
    public function set_text_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);

        $attribute->setValue("foo\r\nbar");

        $this->assertCount(2, $attribute);
        $this->assertSame(['foo', 'bar'], $attribute->getValue());
    }

    #[Test, TestDox('re-set attribute value')]
    public function reset_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);

        $attribute->setValue('foo');
        $attribute->setValue('bar');

        $this->assertCount(1, $attribute);
        $this->assertSame(['bar'], $attribute->getValue());
    }

    #[Test, TestDox('add additional value')]
    public function add_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);

        $attribute->setValue('foo');
        $attribute->addValues('bar');

        $this->assertCount(2, $attribute);
        $this->assertSame(['foo', 'bar'], $attribute->getValue());
    }

    #[Test, TestDox('add value to single attribute is ignored')]
    public function add_single_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::single);

        $attribute->setValue('foo');
        $attribute->addValues('bar');

        $this->assertCount(1, $attribute);
        $this->assertSame('foo', $attribute->getValue());
    }

    #[Test, TestDox('iterate attribute yields value objects')]
    public function iterate_attribute()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);
        $attribute->setValue(['foo', 'bar']);

        $array = iterator_to_array($attribute);

        $this->assertCount(2, $array);
        $this->assertContainsOnlyInstancesOf(Value::class, $array);
    }

    #[Test, TestDox('append value array-style')]
    public function append_value()
    {
        $attribute = new Attribute('test', Presence::mandatory, Repeat::multiple);
        $attribute[] = 'foo';
        $attribute[] = 'bar';

        $this->assertCount(2, $attribute);
        $this->assertSame(['foo', 'bar'], $attribute->getValue());
    }
}
