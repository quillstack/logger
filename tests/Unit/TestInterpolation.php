<?php

declare(strict_types=1);

namespace Quillstack\Logger\Tests\Unit;

use DateTimeImmutable;
use Quillstack\Logger\Logger;
use Quillstack\Logger\Tests\Mocks\MockHandler;
use Quillstack\UnitTests\AssertEqual;

/**
 * PSR-3 §1.2: what is passed alongside the message goes into it.
 */
class TestInterpolation
{
    public function __construct(private AssertEqual $assertEqual)
    {
        //
    }

    /**
     * @param array<string, mixed> $context
     */
    private function written(string $message, array $context): string
    {
        $handler = new MockHandler();
        (new Logger($handler))->info($message, $context);

        return (string) $handler->entries[0]['message'];
    }

    /**
     * The one thing every example of using a logger shows, and the one thing this did not do:
     * `Order {id} placed` used to be written with the braces still in it.
     */
    public function aPlaceholderIsReplacedByWhatTheContextHolds()
    {
        $this->assertEqual->equal(
            'Order 42 placed by ada',
            $this->written('Order {id} placed by {user}', ['id' => 42, 'user' => 'ada'])
        );
    }

    /**
     * What was passed is still passed on, because it is the part something can read.
     */
    public function theContextIsStillHandedOver()
    {
        $handler = new MockHandler();
        (new Logger($handler))->info('Order {id}', ['id' => 42]);

        $this->assertEqual->equal(['id' => 42], $handler->entries[0]['context']);
    }

    /**
     * A placeholder nothing answers is left as it was, rather than emptied — a message
     * reading `Order  placed` says less than one which shows what was expected.
     */
    public function aPlaceholderNothingAnswersIsLeftAlone()
    {
        $this->assertEqual->equal(
            'Order {id} placed',
            $this->written('Order {id} placed', ['user' => 'ada'])
        );
    }

    public function aMessageWithoutPlaceholdersIsUntouched()
    {
        $this->assertEqual->equal(
            'Nothing to replace',
            $this->written('Nothing to replace', ['id' => 42])
        );
    }

    /**
     * Everything readable is read the way a person would write it in a sentence.
     */
    public function everyKindOfValueReadsAsItself()
    {
        $this->assertEqual->equal('null', $this->written('{v}', ['v' => null]));
        $this->assertEqual->equal('true', $this->written('{v}', ['v' => true]));
        $this->assertEqual->equal('false', $this->written('{v}', ['v' => false]));
        $this->assertEqual->equal('1.5', $this->written('{v}', ['v' => 1.5]));
        $this->assertEqual->equal('text', $this->written('{v}', ['v' => 'text']));
    }

    public function somethingWhichCanSayWhatItIsSaysIt()
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'said so';
            }
        };

        $this->assertEqual->equal('said so', $this->written('{v}', ['v' => $stringable]));
    }

    /**
     * Something with no reasonable reading is named rather than mangled: `array` in the
     * middle of a message says more than PHP's `Array` notice would.
     */
    public function somethingWithNoReadingIsNamed()
    {
        $this->assertEqual->equal('array', $this->written('{v}', ['v' => [1, 2]]));
        $this->assertEqual->equal('stdClass', $this->written('{v}', ['v' => new \stdClass()]));
    }

    public function aDateReadsAsADate()
    {
        $this->assertEqual->equal(
            '2026-08-23T10:00:00+00:00',
            $this->written('{v}', ['v' => new DateTimeImmutable('2026-08-23 10:00:00', new \DateTimeZone('UTC'))])
        );
    }

    /**
     * The same placeholder twice is replaced twice.
     */
    public function thesameplaceholderTwiceIsReplacedTwice()
    {
        $this->assertEqual->equal('42 and 42', $this->written('{id} and {id}', ['id' => 42]));
    }
}
