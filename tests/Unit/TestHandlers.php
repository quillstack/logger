<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Unit;

use Quillstack\Logger\Handlers\ConsoleHandler;
use Quillstack\Logger\Logger;
use Quillstack\Logger\Tests\Mocks\MockHandler;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestHandlers
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * A file and a terminal, say — one entry reaching both.
     */
    public function anEntryReachesEveryHandler()
    {
        $first = new MockHandler();
        $second = new MockHandler();

        (new Logger($first))->addHandler($second)->warning('to both');

        $this->assertEqual->equal(['to both'], array_column($first->entries, 'message'));
        $this->assertEqual->equal(['to both'], array_column($second->entries, 'message'));
    }

    /**
     * Setting one replaces whatever was there, which is what the name says.
     */
    public function settingOneReplacesTheRest()
    {
        $first = new MockHandler();
        $second = new MockHandler();

        $logger = (new Logger($first))->addHandler($second)->setHandler(new MockHandler());

        $this->assertEqual->equal(1, count($logger->getHandlers()));

        $logger->warning('to the new one');

        $this->assertEqual->equal([], $first->entries);
        $this->assertEqual->equal([], $second->entries);
    }

    /**
     * A command which fails silently and a command which fails are the same thing to whoever
     * typed it, so a tool with no file to write to still says what happened.
     */
    public function theConsoleHandlerWritesWhereAPersonCanSeeIt()
    {
        ob_start();
        (new Logger(new ConsoleHandler(new Output(new Colors(), false))))
            ->error('Payment failed for {id}', ['id' => 42]);
        $output = (string) ob_get_clean();

        $this->assertBoolean->isTrue(str_contains($output, 'error'));
        $this->assertBoolean->isTrue(str_contains($output, 'Payment failed for 42'));
        $this->assertBoolean->isTrue(str_contains($output, '{"id":42}'));
    }

    /**
     * Escape codes belong on a terminal and nowhere else.
     */
    public function whereItIsNotATerminalTheTextIsTheTextAlone()
    {
        ob_start();
        (new Logger(new ConsoleHandler(new Output(new Colors(), false))))->info('plain');
        $output = (string) ob_get_clean();

        $this->assertBoolean->isFalse(str_contains($output, "\e["));
    }

    public function withColoursTheLevelIsColoured()
    {
        ob_start();
        (new Logger(new ConsoleHandler(new Output(new Colors(), true))))->error('loud');
        $output = (string) ob_get_clean();

        $this->assertBoolean->isTrue(str_contains($output, "\e["));
    }
}
