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
     */
    private ?HandlerInterface $handler;

    public function __construct(?HandlerInterface $handler = null)
    {
        $this->handler = $handler;
    }

    public function setHandler(HandlerInterface $handler): self
    {
        $this->handler = $handler;

        return $this;
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
        if ($this->handler === null) {
            throw new HandlerNotSetException('No handler set, call setHandler() first');
        }

        $this->handler->log($level, $message, $context);
    }
}
