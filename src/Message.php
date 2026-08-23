<?php

declare(strict_types=1);

namespace Quillstack\Logger;

use Stringable;

/**
 * The message, with what was passed alongside it put into it.
 *
 * PSR-3 §1.2: a placeholder written `{id}` is replaced by what the context holds under `id`.
 * Without this, `Order {id} placed` is what ends up in the log — the braces and all — which
 * is the one thing every example of using a logger shows.
 */
final class Message
{
    /**
     * @param array<string, mixed> $context
     */
    public static function interpolate(Stringable|string $message, array $context): string
    {
        $text = (string) $message;

        if (!str_contains($text, '{')) {
            return $text;
        }

        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{' . $key . '}'] = self::text($value);
        }

        return strtr($text, $replacements);
    }

    /**
     * What a value looks like inside a sentence. Anything with no reasonable reading is left
     * as its type, because `Array` in the middle of a message says less than `array` does.
     */
    private static function text(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value), $value instanceof Stringable => (string) $value,
            $value instanceof \DateTimeInterface => $value->format('c'),
            default => get_debug_type($value),
        };
    }
}
