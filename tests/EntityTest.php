<?php

namespace Dormilich\RPSL\Tests;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Container;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Entity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Entity::class)]
#[UsesClass(Attribute::class), UsesClass(Container::class), UsesClass(Value::class)]
class EntityTest extends TestCase
{
    #[Test, TestDox('create object with a handle')]
    public function init_with_handle()
    {
        $object = new RpslObject('phpunit');

        $this->assertSame('phpunit', $object->getHandle());
        $this->assertSame('test', $object->getType());
    }

    #[Test, TestDox('access attributes by methods')]
    public function attribute_access()
    {
        $object = new RpslObject();

        $this->assertTrue($object->has('foo'), 'the "foo" attribute is missing');
        $this->assertFalse($object->has('bar'), 'the "bar" attribute should not exist');

        $object->set('foo', 'bar');

        $this->assertNull($object->get('test'));
        $this->assertSame(['bar'], $object->get('foo'));

        $object->add('foo', 'baz');

        $this->assertSame(['bar', 'baz'], $object->get('foo'));
    }

    #[Test, TestDox('access attributes by array')]
    public function array_access()
    {
        $object = new RpslObject();

        $this->assertTrue(isset($object['foo']), 'the "foo" attribute is missing');
        $this->assertFalse(isset($object['bar']), 'the "bar" attribute should not exist');
        $this->assertCount(0, $object);
        $this->assertEmpty($object['test']);

        $object['foo'] = 'bar';

        $this->assertSame(['bar'], $object->get('foo'));

        $object['foo'][] = 'baz';

        $this->assertSame(['bar', 'baz'], $object->get('foo'));
        $this->assertCount(1, $object);
    }

    #[Test, TestDox('object with defined mandatory attributes is valid')]
    public function check_validity()
    {
        $object = new RpslObject();

        $this->assertFalse($object->isValid(), 'the object should not be valid');

        $object->set($object->getType(), 'phpunit');

        $this->assertTrue($object->isValid(), 'the object should be valid');
    }

    #[Test, TestDox('get all defined attributes')]
    public function get_attributes()
    {
        $object = new RpslObject('phpunit');
        $attributes = $object->getAttributes();

        $this->assertCount(1, $attributes);
        $this->assertContainsOnlyInstancesOf(Attribute::class, $attributes);
        $this->assertSame('test', $attributes[0]->getName());
    }

    #[Test, TestDox('get all attribute values')]
    public function get_values()
    {
        $object = new RpslObject('phpunit');
        $values = $object->getValues();

        $this->assertCount(1, $values);
        $this->assertContainsOnlyInstancesOf(Value::class, $values);
        $this->assertSame('test', $values[0]->getName());
        $this->assertSame('phpunit', $values[0]->getValue());
    }
}
