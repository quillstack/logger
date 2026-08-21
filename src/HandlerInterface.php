<?php

declare(strict_types=1);

namespace Quillstack\Logger;

use Stringable;

interface HandlerInterface
{
    /**
     * Writes one entry, and tells whether it was written.
     *
     * @param mixed $level one of the PSR-3 levels
     * @param array<string, mixed> $context
     */
    public function log(mixed $level, Stringable|string $message, array $context = []): bool;
}
