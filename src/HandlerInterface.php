<?php

declare(strict_types=1);

namespace Quillstack\Logger;

interface HandlerInterface
{
    public function log($level, \Stringable|string $message, array $context = []): bool;
}
