<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Unit;

use Psr\Log\LogLevel;
use Quillstack\Logger\Handlers\StorageHandler;
use Quillstack\Logger\Logger;
use Quillstack\Logger\Tests\Mocks\MockStorage;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestStorageHandler
{
    private const PATH = '/var/log/quillstack.log';

    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * Drops the leading timestamp so the rest of the line can be compared.
     */
    private function withoutTimestamps(string $contents): array
    {
        $lines = [];

        foreach (explode(PHP_EOL, trim($contents, PHP_EOL)) as $line) {
            $lines[] = substr($line, strpos($line, ' ') + 1);
        }

        return $lines;
    }

    /**
     * Entries used to be written with save(), which overwrites, so the log only ever held
     * the last line.
     */
    public function entriesAreAppended()
    {
        $storage = new MockStorage();
        $logger = new Logger(new StorageHandler(self::PATH, $storage));

        $logger->info('first');
        $logger->info('second');

        $this->assertEqual->equal(
            ['info: first', 'info: second'],
            $this->withoutTimestamps($storage->get(self::PATH))
        );
    }

    public function theContextIsWrittenAsJson()
    {
        $storage = new MockStorage();
        (new Logger(new StorageHandler(self::PATH, $storage)))->error('failed', ['code' => 500]);

        $this->assertEqual->equal(
            ['error: failed {"code":500}'],
            $this->withoutTimestamps($storage->get(self::PATH))
        );
    }

    public function anEmptyContextIsLeftOut()
    {
        $storage = new MockStorage();
        (new Logger(new StorageHandler(self::PATH, $storage)))->warning('careful');

        $this->assertEqual->equal(
            ['warning: careful'],
            $this->withoutTimestamps($storage->get(self::PATH))
        );
    }

    public function everyEntryStartsWithATimestamp()
    {
        $storage = new MockStorage();
        (new Logger(new StorageHandler(self::PATH, $storage)))->debug('when');

        $this->assertBoolean->isTrue(
            (bool) preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2} debug: when$/', trim($storage->get(self::PATH)))
        );
    }

    public function theHandlerReportsThatItWrote()
    {
        $handler = new StorageHandler(self::PATH, new MockStorage());

        $this->assertBoolean->isTrue($handler->log(LogLevel::INFO, 'written'));
    }
}
