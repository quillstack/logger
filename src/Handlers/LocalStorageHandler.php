<?php

declare(strict_types=1);

namespace Quillstack\Logger\Handlers;

use Quillstack\LocalStorage\LocalStorage;
use Quillstack\Logger\HandlerInterface;

class LocalStorageHandler implements HandlerInterface
{
    public function __construct(private string $path, private LocalStorage $localStorage)
    {
        //
    }

    public function log($level, \Stringable|string $message, array $context = []): bool
    {
        $line = $level . ' ' . $message . ' ' . json_encode($context);

        return $this->localStorage->save($this->path, $line);
    }
}
