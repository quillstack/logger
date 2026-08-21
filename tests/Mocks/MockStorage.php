<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Mocks;

use Quillstack\StorageInterface\StorageInterface;

/**
 * An in-memory storage keeping save() and add() apart, so a test can tell whether the
 * handler appended an entry or wrote over the ones before it.
 */
class MockStorage implements StorageInterface
{
    public array $files = [];

    /**
     * {@inheritDoc}
     */
    public function get(string $path): mixed
    {
        return $this->files[$path] ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $path): bool
    {
        return isset($this->files[$path]);
    }

    /**
     * {@inheritDoc}
     */
    public function missing(string $path): bool
    {
        return !$this->exists($path);
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $path, mixed $contents): bool
    {
        $this->files[$path] = (string) $contents;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $path, mixed $contents): bool
    {
        $this->files[$path] = ($this->files[$path] ?? '') . $contents;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $path, string ...$more): bool
    {
        foreach ([$path, ...$more] as $one) {
            unset($this->files[$one]);
        }

        return true;
    }
}
