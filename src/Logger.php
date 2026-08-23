<?php

declare(strict_types=1);

namespace Quillstack\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Quillstack\Logger\Exceptions\HandlerNotSetException;
use Stringable;

class Logger implements LoggerInterface
{
    /**
     * Where the entries are written. Without one there is nowhere to log to, so `log()`
     * says so instead of failing on an uninitialised property.
     *
     * @var array<int, HandlerInterface>
     */
    private array $handlers = [];

    /**
     * The least serious level worth writing. Anything below it is not passed on, which is how
     * `debug` is turned off on a server without taking the calls out of the code.
     */
    private string $minimumLevel;

    public function __construct(?HandlerInterface $handler = null, string $minimumLevel = LogLevel::DEBUG)
    {
        if ($handler !== null) {
            $this->handlers[] = $handler;
        }

        $this->minimumLevel = Level::name($minimumLevel);
    }

    /**
     * The one handler, replacing whatever was there.
     */
    public function setHandler(HandlerInterface $handler): self
    {
        $this->handlers = [$handler];

        return $this;
    }

    /**
     * One more handler beside the ones there are — a file and a terminal, say.
     */
    public function addHandler(HandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @return array<int, HandlerInterface>
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Raises or lowers what is worth writing.
     */
    public function setMinimumLevel(string $level): self
    {
        $this->minimumLevel = Level::name($level);

        return $this;
    }

    public function getMinimumLevel(): string
    {
        return $this->minimumLevel;
    }

    /**
     * {@inheritDoc}
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        // A level nobody knows is refused before anything else happens, which is what PSR-3
        // asks for — writing the entry anyway means a level nothing can filter on.
        $name = Level::name($level);

        if ($this->handlers === []) {
            throw new HandlerNotSetException('No handler set, call setHandler() first');
        }

        if (!Level::reaches($name, $this->minimumLevel)) {
            return;
        }

        $text = Message::interpolate($message, $context);

        foreach ($this->handlers as $handler) {
            $handler->log($name, $text, $context);
        }
    }
}
