<?php

namespace Dormilich\RPSL\Tests\Attribute;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Container;
use Dormilich\RPSL\Attribute\Presence;
use Dormilich\RPSL\Attribute\Repeat;
use Dormilich\RPSL\Attribute\Value;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[UsesClass(Attribute::class), UsesClass(Value::class)]
class ContainerTest extends TestCase
{
    private function container(): Container
    {
        $data = new Container();
        $data->add(new Attribute('test', Presence::primary_key, Repeat::single));
        $data->add(new Attribute('foo', Presence::optional, Repeat::multiple));

        return $data;
    }

    #[Test, TestDox('attribute exists')]
    public function has_attribute()
    {
        $container = $this->container();

        $this->assertTrue($container->has('test'), 'test attribute does not exist');
        $this->assertFalse($container->has('bar'), 'bar attribute exists');
    }

    #[Test, TestDox('get attribute')]
    public function get_existing_attribute()
    {
        $attribute = $this->container()->get('test');

        $this->assertInstanceOf(Attribute::class, $attribute);
        $this->assertSame('test', $attribute->getName());
    }

    #[Test, TestDox('get attribute')]
    public function get_unknown_attribute()
    {
        $attribute = $this->container()->get('bar');

        $this->assertNull($attribute);
    }

    #[Test, TestDox('retain attributes by condition')]
    public function select_truthy()
    {
        $retain = $this->container()->retain(fn(Attribute $a) => $a->isPrimaryKey());

        $this->assertCount(1, $retain);
        $this->assertSame('test', $retain->current()->getName());
        $this->assertTrue($retain->current()->isPrimaryKey(), 'attribute is not a primary key');
    }

    #[Test, TestDox('retain attributes by inverse condition')]
    public function select_falsy()
    {
        $reject = $this->container()->reject(fn(Attribute $a) => $a->isPrimaryKey());

        $this->assertCount(1, $reject);
        $this->assertSame('foo', $reject->current()->getName());
        $this->assertFalse($reject->current()->isPrimaryKey(), 'attribute is a primary key');
    }

    #[Test, TestDox('map attributes to array')]
    public function map_attributes()
    {
        $map = $this->container()->map(fn(Attribute $a) => $a->isMandatory());

        $this->assertSame(['test' => true, 'foo' => false], $map);
    }

    #[Test, TestDox('reduce attributes to single value')]
    public function reduce_attributes()
    {
        $reduce = $this->container()->reduce('', fn(string $name, Attribute $a) => $name . $a->getName());

        $this->assertSame('testfoo', $reduce);
    }

    #[Test, TestDox('iterate over the attributes')]
    public function iterate_attributes()
    {
        $array = iterator_to_array($this->container());

        $this->assertCount(2, $array);
        $this->assertContainsOnlyInstancesOf(Attribute::class, $array);
    }

    #[Test, TestDox('iterate over the attribute values')]
    public function iterate_values()
    {
        $container = $this->container();
        $container->get('test')->setValue('phpunit');
        $container->get('foo')->setValue(['bar', 'baz']);
        $iterator = new \RecursiveIteratorIterator($container);
        $array = iterator_to_array($iterator, false);

        $this->assertCount(3, $array);
        $this->assertContainsOnlyInstancesOf(Value::class, $array);
    }
}
