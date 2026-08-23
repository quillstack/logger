<?php

declare(strict_types=1);

namespace Quillstack\Logger\Handlers;

use Quillstack\Logger\Entry;
use Quillstack\Logger\Exceptions\HandlerException;
use Quillstack\Logger\HandlerInterface;
use Stringable;

/**
 * Appends entries to a file, keeping it open.
 *
 * `StorageHandler` beside this one writes through a `StorageInterface`, which knows how to put
 * contents at a path and nothing about a handle — so every entry costs an open, a lock, a write
 * and a close. That abstraction is worth having where entries go somewhere that is not a local
 * file; where they go to a local file it costs about three times the time, which is what this
 * exists to stop paying.
 *
 * The file is opened for appending, so each write lands at the end no matter how many processes
 * are writing. On a local filesystem the kernel makes a single small append atomic, which is why
 * nothing is locked by default; `locking: true` adds a lock for the cases where that guarantee
 * does not hold, such as a file on NFS.
 */
final class FileHandler implements HandlerInterface
{
    /**
     * @var resource|null
     */
    private mixed $handle = null;

    public function __construct(
        private readonly string $path,
        private readonly bool $locking = false
    ) {
        //
    }

    /**
     * The handle is closed when nothing holds this any more. PHP would do it at the end of the
     * process anyway; doing it here is what makes a handler that goes out of scope release the
     * file rather than waiting.
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function log(mixed $level, Stringable|string $message, array $context = []): bool
    {
        $handle = $this->handle();
        $line = Entry::line($level, $message, $context);

        if (!$this->locking) {
            return fwrite($handle, $line) !== false;
        }

        if (!flock($handle, LOCK_EX)) {
            return false;
        }

        $written = fwrite($handle, $line);
        flock($handle, LOCK_UN);

        return $written !== false;
    }

    /**
     * Opened on the first entry rather than in the constructor: a handler which is configured
     * and never used should not create a file.
     *
     * @return resource
     */
    private function handle(): mixed
    {
        if (is_resource($this->handle)) {
            return $this->handle;
        }

        $handle = @fopen($this->path, 'a');

        if ($handle === false) {
            throw new HandlerException("Unable to open `{$this->path}` for writing");
        }

        return $this->handle = $handle;
    }
}
