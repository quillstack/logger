<?php

declare(strict_types=1);

namespace Quillstack\Logger;

use Stringable;

interface HandlerInterface
{
    /**
     * Writes one entry, and tells whether it was written.
     */
    public function log($level, Stringable|string $message, array $context = []): bool;
}
