<?php

declare(strict_types=1);

namespace Quillstack\Logger\Exceptions;

use Quillstack\Logger\LoggerException;

/**
 * Thrown where a handler cannot write at all — a path that cannot be opened, rather than an
 * entry that failed.
 */
class HandlerException extends LoggerException
{
}
