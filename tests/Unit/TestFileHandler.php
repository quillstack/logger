<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Unit;

use Quillstack\Logger\Exceptions\HandlerException;
use Quillstack\Logger\Handlers\FileHandler;
use Quillstack\Logger\Logger;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * The handler that keeps the file open.
 *
 * `StorageHandler` beside it writes through a `StorageInterface`, which has no notion of a held
 * handle, so every entry costs an open, a lock, a write and a close — about three times the
 * time, measured against Monolog.
 */
class TestFileHandler
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
    ) {
        //
    }

    private function path(string $name): string
    {
        return sys_get_temp_dir() . "/quillstack-logger-{$name}.log";
    }

    public function entriesAreAppendedOneToALine()
    {
        $path = $this->path('lines');
        @unlink($path);

        $logger = new Logger(new FileHandler($path));
        $logger->error('First');
        $logger->info('Second');

        $lines = file($path) ?: [];

        $this->assertEqual->equal(2, count($lines));
        $this->assertBoolean->isTrue(str_contains($lines[0], 'error: First'));
        $this->assertBoolean->isTrue(str_contains($lines[1], 'info: Second'));

        unlink($path);
    }

    /**
     * The same line the storage handler writes, because both go through `Entry`.
     */
    public function placeholdersAreFilledIn()
    {
        $path = $this->path('placeholders');
        @unlink($path);

        (new Logger(new FileHandler($path)))
            ->error('Payment failed for {id} after {attempts} tries', ['id' => 42, 'attempts' => 3]);

        $written = (string) file_get_contents($path);

        $this->assertBoolean->isTrue(str_contains($written, 'Payment failed for 42 after 3 tries'));
        $this->assertBoolean->isTrue(str_contains($written, '{"id":42,"attempts":3}'));

        unlink($path);
    }

    /**
     * A handler which is configured and never used should not create a file.
     */
    public function nothingIsOpenedUntilSomethingIsWritten()
    {
        $path = $this->path('unused');
        @unlink($path);

        new FileHandler($path);

        $this->assertBoolean->isFalse(is_file($path));
    }

    public function aPathThatCannotBeOpenedSaysSo()
    {
        $this->assertExceptions->expect(HandlerException::class);

        (new FileHandler('/nowhere-at-all/quillstack.log'))->log('error', 'x');
    }

    /**
     * Opened for appending, so a second handler on the same file adds to it rather than
     * writing over what is there.
     */
    public function aSecondHandlerAppendsRatherThanOverwrites()
    {
        $path = $this->path('append');
        @unlink($path);

        (new FileHandler($path))->log('info', 'First');
        (new FileHandler($path))->log('info', 'Second');

        $this->assertEqual->equal(2, count(file($path) ?: []));

        unlink($path);
    }

    public function itCanLockWhereThatGuaranteeIsNeeded()
    {
        $path = $this->path('locking');
        @unlink($path);

        $this->assertBoolean->isTrue((new FileHandler($path, locking: true))->log('info', 'Locked'));
        $this->assertEqual->equal(1, count(file($path) ?: []));

        unlink($path);
    }
}
