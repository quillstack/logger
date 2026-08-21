<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Mocks;

use Quillstack\Logger\HandlerInterface;
use Stringable;

/**
 * Keeps what it was given, so a test can look at it.
 */
class MockHandler implements HandlerInterface
{
    public array $entries = [];

    public function log($level, Stringable|string $message, array $context = []): bool
    {
        $this->entries[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];

        return true;
    }
}
