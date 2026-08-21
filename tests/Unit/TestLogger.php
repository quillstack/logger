<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Unit;

use Psr\Log\LogLevel;
use Quillstack\Logger\Exceptions\HandlerNotSetException;
use Quillstack\Logger\Logger;
use Quillstack\Logger\Tests\Mocks\MockHandler;
use Quillstack\Logger\Tests\Mocks\MockMessage;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\AssertExceptions;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestLogger
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertExceptions $assertExceptions,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * Without a handler there is nowhere to write. The property used to have no default,
     * so reading it raised an Error about an uninitialised property instead.
     */
    public function loggingWithoutAHandlerIsReported()
    {
        $this->assertExceptions->expect(HandlerNotSetException::class);

        (new Logger())->info('nowhere to go');
    }

    public function theHandlerCanBeGivenToTheConstructor()
    {
        $handler = new MockHandler();
        (new Logger($handler))->info('hello');

        $this->assertEqual->equal(1, count($handler->entries));
    }

    public function theHandlerCanBeSetLater()
    {
        $handler = new MockHandler();
        (new Logger())->setHandler($handler)->info('hello');

        $this->assertEqual->equal('hello', $handler->entries[0]['message']);
    }

    public function everyLevelReachesTheHandler()
    {
        $handler = new MockHandler();
        $logger = new Logger($handler);

        $logger->emergency('a');
        $logger->alert('b');
        $logger->critical('c');
        $logger->error('d');
        $logger->warning('e');
        $logger->notice('f');
        $logger->info('g');
        $logger->debug('h');

        $this->assertEqual->equal([
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ], array_column($handler->entries, 'level'));
    }

    public function theContextIsPassedOn()
    {
        $handler = new MockHandler();
        (new Logger($handler))->error('failed', ['code' => 500]);

        $this->assertEqual->equal(['code' => 500], $handler->entries[0]['context']);
    }

    public function aStringableMessageIsAccepted()
    {
        $handler = new MockHandler();
        (new Logger($handler))->info(new MockMessage());

        $this->assertEqual->equal('message from an object', $handler->entries[0]['message']);
    }

    public function setHandlerReturnsTheLogger()
    {
        $logger = new Logger();

        $this->assertBoolean->isTrue($logger->setHandler(new MockHandler()) === $logger);
    }
}
