<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Unit;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Quillstack\Logger\Exceptions\UnknownLevelException;
use Quillstack\Logger\Level;
use Quillstack\Logger\Logger;
use Quillstack\Logger\Tests\Mocks\MockHandler;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestLevels
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean,
        private AssertExceptions $assertExceptions
    ) {
        //
    }

    /**
     * The eight of PSR-3, and no others.
     */
    public function theLevelsAreTheEightOfTheSpecification()
    {
        $this->assertEqual->equal(
            ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
            array_keys(Level::SEVERITY)
        );
    }

    public function theyRankAgainstEachOther()
    {
        $this->assertBoolean->isTrue(Level::reaches(LogLevel::ERROR, LogLevel::WARNING));
        $this->assertBoolean->isTrue(Level::reaches(LogLevel::WARNING, LogLevel::WARNING));
        $this->assertBoolean->isFalse(Level::reaches(LogLevel::INFO, LogLevel::WARNING));
        $this->assertBoolean->isTrue(Level::reaches(LogLevel::EMERGENCY, LogLevel::DEBUG));
    }

    /**
     * A level nobody knows used to be written anyway — a level nothing could filter on, and
     * a typo that never surfaced. PSR-3 names the exception it wants.
     */
    public function anUnknownLevelIsRefused()
    {
        $this->assertExceptions->expect(UnknownLevelException::class);

        (new Logger(new MockHandler()))->log('verbose', 'anything');
    }

    /**
     * And it is the one the specification names, so anything catching what PSR-3 says to
     * catch catches this.
     */
    public function itIsTheExceptionTheSpecificationNames()
    {
        $caught = null;

        try {
            (new Logger(new MockHandler()))->log('verbose', 'anything');
        } catch (InvalidArgumentException $exception) {
            $caught = $exception;
        }

        $this->assertBoolean->isTrue($caught !== null);
        $this->assertBoolean->isTrue(str_contains((string) $caught?->getMessage(), 'Unknown level: verbose'));
        $this->assertBoolean->isTrue(str_contains((string) $caught?->getMessage(), 'debug, info, notice'));
    }

    public function nothingIsWrittenForALevelThatWasRefused()
    {
        $handler = new MockHandler();

        try {
            (new Logger($handler))->log('verbose', 'anything');
        } catch (InvalidArgumentException) {
            // Refused, which is the point.
        }

        $this->assertEqual->equal([], $handler->entries);
    }

    /**
     * The case it was written in does not matter; the level does.
     */
    public function theCaseDoesNotMatter()
    {
        $handler = new MockHandler();
        (new Logger($handler))->log('ERROR', 'shouted');

        $this->assertEqual->equal('error', $handler->entries[0]['level']);
    }

    /**
     * Anything below the minimum is not passed on, which is how `debug` is turned off on a
     * server without taking the calls out of the code.
     */
    public function belowTheMinimumIsNotWritten()
    {
        $handler = new MockHandler();
        $logger = new Logger($handler, LogLevel::WARNING);

        $logger->debug('not this');
        $logger->info('nor this');
        $logger->warning('this one');
        $logger->error('and this');

        $this->assertEqual->equal(
            ['this one', 'and this'],
            array_column($handler->entries, 'message')
        );
    }

    public function everythingIsWrittenByDefault()
    {
        $handler = new MockHandler();
        $logger = new Logger($handler);

        $logger->debug('the quietest there is');

        $this->assertEqual->equal(1, count($handler->entries));
        $this->assertEqual->equal('debug', $logger->getMinimumLevel());
    }

    public function theMinimumCanBeChanged()
    {
        $handler = new MockHandler();
        $logger = new Logger($handler);

        $logger->setMinimumLevel(LogLevel::ERROR);
        $logger->warning('not any more');
        $logger->error('still this');

        $this->assertEqual->equal(['still this'], array_column($handler->entries, 'message'));
        $this->assertEqual->equal('error', $logger->getMinimumLevel());
    }

    public function aMinimumNobodyKnowsIsRefusedToo()
    {
        $this->assertExceptions->expect(UnknownLevelException::class);

        new Logger(new MockHandler(), 'verbose');
    }
}
