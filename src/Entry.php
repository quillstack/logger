<?php

declare(strict_types=1);

namespace Quillstack\Logger;

use Stringable;

/**
 * One entry, as a line.
 *
 * Both file-writing handlers produce the same shape, and they produced it twice until this
 * existed: when it happened, at which level, what was said, and what was passed along.
 */
final class Entry
{
    /**
     * @param array<string, mixed> $context
     */
    public static function line(mixed $level, Stringable|string $message, array $context = []): string
    {
        $line = date('c') . ' ' . (is_scalar($level) ? (string) $level : '') . ': ' . $message;

        if ($context !== []) {
            $line .= ' ' . json_encode($context);
        }

        return $line . PHP_EOL;
    }
}
