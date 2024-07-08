<?php

namespace Dormilich\RPSL\Tests;

use Dormilich\RPSL\Attribute\Presence;
use Dormilich\RPSL\Attribute\Repeat;
use Dormilich\RPSL\Entity;

class RpslObject extends Entity
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->create('test', Presence::primary_key, Repeat::single);
        $this->create('foo', Presence::optional, Repeat::multiple);
    }
}
