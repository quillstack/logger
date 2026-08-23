<?php

declare(strict_types=1);

namespace Quillstack\Logger;

use Psr\Log\LogLevel;
use Quillstack\Logger\Exceptions\UnknownLevelException;

/**
 * The eight levels of PSR-3, and how they rank against each other.
 *
 * They are strings in the specification and strings on the way in, but a logger has to know
 * that an error matters more than a notice — otherwise there is no such thing as a minimum
 * level.
 */
final class Level
{
    /**
     * From the least serious to the most, which is the order a threshold is read in.
     *
     * @var array<string, int>
     */
    public const SEVERITY = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    /**
     * The level as one of the eight, or a refusal.
     *
     * PSR-3 asks for `Psr\Log\InvalidArgumentException` where it is not one of them. Writing
     * the entry anyway, which is what used to happen, means a level nobody can filter on and
     * a typo that never surfaces.
     */
    public static function name(mixed $level): string
    {
        $name = is_string($level) ? strtolower($level) : '';

        if (!isset(self::SEVERITY[$name])) {
            $known = implode(', ', array_keys(self::SEVERITY));
            $given = is_scalar($level) ? (string) $level : get_debug_type($level);

            throw new UnknownLevelException("Unknown level: {$given}, one of {$known} expected");
        }

        return $name;
    }

    /**
     * Whether one level is worth writing where another is the least that will be.
     */
    public static function reaches(string $level, string $minimum): bool
    {
        return self::SEVERITY[$level] >= self::SEVERITY[$minimum];
    }
}
