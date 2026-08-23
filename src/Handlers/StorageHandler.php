<?php

declare(strict_types=1);

namespace Quillstack\Logger\Handlers;

use Quillstack\Logger\Entry;
use Quillstack\Logger\HandlerInterface;
use Quillstack\StorageInterface\StorageInterface;
use Stringable;

/**
 * Appends entries to a file through any storage, which is why it takes the interface and
 * not one implementation of it.
 */
class StorageHandler implements HandlerInterface
{
    public function __construct(private string $path, private StorageInterface $storage)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function log(mixed $level, Stringable|string $message, array $context = []): bool
    {
        return $this->storage->add(
            $this->path,
            Entry::line($level, $message, $context)
        );
    }
}
