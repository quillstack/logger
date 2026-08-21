# Quillstack Logger

[![Build Status](https://app.travis-ci.com/quillstack/logger.svg?branch=main)](https://app.travis-ci.com/quillstack/logger)

A simple logger based on PSR-3: Logger Interface.

## Installation

```shell
composer require quillstack/logger
```

## Usage

A logger writes through a handler. `StorageHandler` appends entries to a file:

```php
use Quillstack\LocalStorage\LocalStorage;
use Quillstack\Logger\Handlers\StorageHandler;
use Quillstack\Logger\Logger;

$logger = new Logger(
    new StorageHandler(__DIR__ . '/var/quillstack.log', new LocalStorage())
);

$logger->info('User signed in', ['id' => 42]);
$logger->error('Payment failed', ['code' => 500]);
```

Which writes one entry per line:

```
2026-08-21T16:20:41+02:00 info: User signed in {"id":42}
2026-08-21T16:20:41+02:00 error: Payment failed {"code":500}
```

The handler can be set after the logger was built, which is what a container does:

```php
$logger = new Logger();
$logger->setHandler($handler);
```

Logging without a handler throws `HandlerNotSetException`.

## Handlers

`StorageHandler` takes any `Quillstack\StorageInterface\StorageInterface`, so anything
implementing it can hold the log. To write somewhere else entirely, implement
`Quillstack\Logger\HandlerInterface`:

```php
use Quillstack\Logger\HandlerInterface;

final class SyslogHandler implements HandlerInterface
{
    public function log($level, \Stringable|string $message, array $context = []): bool
    {
        return syslog(LOG_INFO, "{$level}: {$message}");
    }
}
```

## Tests

```shell
composer test
```

Coverage needs phpdbg:

```shell
composer test:coverage
```

## License

MIT. See [LICENSE](LICENSE).
