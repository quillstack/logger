<?php

declare(strict_types=1);

namespace Quillstack\Logger\Exceptions;

use Psr\Log\InvalidArgumentException;

/**
 * PSR-3 names the exception a logger throws for a level it does not know, so this is that one
 * — anything catching what the specification says to catch catches this.
 */
class UnknownLevelException extends InvalidArgumentException
{
    //
}
