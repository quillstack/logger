<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Mocks;

use Stringable;

class MockMessage implements Stringable
{
    public function __toString(): string
    {
        return 'message from an object';
    }
}
