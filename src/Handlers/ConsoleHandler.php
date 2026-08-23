<?php

declare(strict_types=1);

namespace Quillstack\Logger\Handlers;

use Quillstack\Logger\HandlerInterface;
use Quillstack\Logger\Level;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;
use Stringable;

/**
 * Writes entries where a person watching can see them.
 *
 * A command which fails silently and a command which fails are the same thing to whoever
 * typed it, so a tool with no file to write to still has somewhere to say what happened.
 */
class ConsoleHandler implements HandlerInterface
{
    /**
     * The colour each level is written in. Anything at error or worse is red, because a wall
     * of one colour is a wall nobody reads.
     *
     * @var array<string, string>
     */
    private const COLOURS = [
        'debug' => 'dark-grey',
        'info' => 'green',
        'notice' => 'green',
        'warning' => 'yellow',
        'error' => 'red',
        'critical' => 'red',
        'alert' => 'red',
        'emergency' => 'red',
    ];

    private readonly OutputInterface $output;

    public function __construct(?OutputInterface $output = null)
    {
        $this->output = $output ?? new Output(new Colors(), self::isTerminal());
    }

    /**
     * {@inheritDoc}
     */
    public function log(mixed $level, Stringable|string $message, array $context = []): bool
    {
        $name = Level::name($level);
        $colour = self::COLOURS[$name];
        $line = "<dark-grey>" . date('H:i:s') . "</dark-grey> <{$colour}>{$name}</{$colour}> {$message}";

        if ($context !== []) {
            $encoded = json_encode($context);
            $line .= ' <dark-grey>' . ($encoded === false ? '' : $encoded) . '</dark-grey>';
        }

        $this->output->writeln($line);

        return true;
    }

    /**
     * Escape codes belong on a terminal and nowhere else, so output piped into a file is the
     * text alone.
     */
    private static function isTerminal(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }
}
