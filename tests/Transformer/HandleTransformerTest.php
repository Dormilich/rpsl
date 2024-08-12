<?php

namespace Dormilich\RPSL\Tests\Transformer;

use Dormilich\RPSL\Attribute\Attribute;
use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;
use Dormilich\RPSL\FactoryInterface;
use Dormilich\RPSL\ObjectInterface;
use Dormilich\RPSL\Transformer\DefaultTransformer;
use Dormilich\RPSL\Transformer\HandleTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HandleTransformer::class)]
#[UsesClass(DefaultTransformer::class)]
#[UsesClass(Value::class)]
class HandleTransformerTest extends TestCase
{
    private function transformer(FactoryInterface $factory): DefaultTransformer
    {
        $attribute = $this->createConfiguredStub(Attribute::class, [
            'getName' => 'test',
        ]);

        $transformer = new HandleTransformer($factory);

        return $transformer->setAttribute($attribute);
    }

    #[Test, TestDox('retrieve typed value as object')]
    public function get_object()
    {
        $object = $this->createConfiguredStub(ObjectInterface::class, [
            'getHandle' => 'TEST',
            'getType' => 'role',
        ]);
        $factory = $this->createMock(FactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo('role'),
                $this->identicalTo('TEST')
            )
            ->willReturn($object);
        $transformer = $this->transformer($factory);
        $value = $transformer->serialize($object);
        $data = $transformer->unserialize($value);

        $this->assertSame($object, $data);
    }

    #[Test, TestDox('retrieve untyped value as string')]
    public function get_plain_value()
    {
        $factory = $this->createMock(FactoryInterface::class);
        $factory
            ->expects($this->never())
            ->method('create');
        $transformer = $this->transformer($factory);
        $value = $transformer->serialize('TEST');
        $data = $transformer->unserialize($value);

        $this->assertSame('TEST', $data);
    }

    #[Test, TestDox('retrieve empty value as NULL')]
    public function get_empty_value()
    {
        $factory = $this->createMock(FactoryInterface::class);
        $factory
            ->expects($this->never())
            ->method('create');
        $transformer = $this->transformer($factory);
        $value = $transformer->serialize('');
        $data = $transformer->unserialize($value);

        $this->assertNull($data);
    }

    #[Test, TestDox('handle transformation failure')]
    public function transformation_failure()
    {
        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage('Failed to transform "invalid" (TEST) into an RPSL object');

        $object = $this->createConfiguredStub(ObjectInterface::class, [
            'getHandle' => 'TEST',
            'getType' => 'invalid',
        ]);
        $factory = $this->createMock(FactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo('invalid'),
            )
            ->willThrowException(new \OutOfBoundsException('Type "invalid" does not exist.'));
        $transformer = $this->transformer($factory);
        $value = $transformer->serialize($object);
        $transformer->unserialize($value);
    }
}
