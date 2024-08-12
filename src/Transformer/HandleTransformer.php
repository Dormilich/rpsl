<?php

namespace Dormilich\RPSL\Transformer;

use Dormilich\RPSL\Attribute\Value;
use Dormilich\RPSL\Exception\TransformerException;
use Dormilich\RPSL\FactoryInterface;
use Dormilich\RPSL\ObjectInterface;
use Exception;

use function sprintf;

class HandleTransformer extends DefaultTransformer
{
    /**
     * @param FactoryInterface $factory
     */
    public function __construct(private readonly FactoryInterface $factory)
    {
    }

    /**
     * @inheritDoc
     */
    public function unserialize(Value $value): mixed
    {
        if ($value->isEmpty()) {
            return null;
        } elseif ($object = $this->getObject($value)) {
            return $object;
        } else {
            return $value->getValue();
        }
    }

    /**
     * @param Value $value
     * @return ObjectInterface|null
     * @throws TransformerException
     */
    private function getObject(Value $value): ?ObjectInterface
    {
        $type = $value->getType();
        $handle = $value->getValue();

        if (null === $type) {
            return null;
        }

        try {
            return $this->factory->create($type, $handle);
        } catch (Exception $e) {
            $message = sprintf('Failed to transform "%s" (%s) into an RPSL object.', $type, $handle);
            throw new TransformerException($message, 0, $e);
        }
    }
}
