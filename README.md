# Quillstack Logger

[![Tests](https://github.com/quillstack/logger/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/logger/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/logger.svg)](https://packagist.org/packages/quillstack/logger)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/logger.svg)](https://packagist.org/packages/quillstack/logger)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/logger)](https://packagist.org/packages/quillstack/logger)
[![StyleCI](https://github.styleci.io/repos/448654887/shield?branch=main)](https://github.styleci.io/repos/448654887?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/logger/badge)](https://www.codefactor.io/repository/github/quillstack/logger)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=quillstack_logger&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_logger)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_logger&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_logger)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_logger&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_logger)
[![Reliability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_logger&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_logger)
[![Security](https://sonarcloud.io/api/project_badges/measure?project=quillstack_logger&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_logger)
[![License](https://img.shields.io/packagist/l/quillstack/logger)](https://github.com/quillstack/logger/blob/main/LICENSE)

A logger based on [PSR-3](https://www.php-fig.org/psr/psr-3/), with no dependencies beyond it.
Full documentation: https://quillstack.org/logger

Somewhere to write what happened, and a way to say how much of it is worth writing. A handler
decides where the entries go; there is no formatter to configure and no processor to register.

### Requirements

- PHP 8.1 or newer

### Installation

```shell
composer require quillstack/logger
```

### Usage

```php
use Quillstack\LocalStorage\LocalStorage;
use Quillstack\Logger\Handlers\StorageHandler;
use Quillstack\Logger\Logger;

$logger = new Logger(new StorageHandler('/var/log/app.log', new LocalStorage()));

$logger->info('Order {id} placed by {user}', ['id' => 42, 'user' => 'ada']);
```

```text
2026-08-23T10:44:17+00:00 info: Order 42 placed by ada {"id":42,"user":"ada"}
```

The eight methods of PSR-3 are there — `emergency()`, `alert()`, `critical()`, `error()`,
`warning()`, `notice()`, `info()`, `debug()` — and `log()` takes the level as an argument.

### What is passed alongside goes into the message

A placeholder written `{id}` is replaced by what the context holds under `id`, which is what
PSR-3 §1.2 describes and what every example of using a logger shows:

```php
$logger->error('Payment failed for {id} after {attempts} tries', ['id' => 42, 'attempts' => 3]);
// Payment failed for 42 after 3 tries
```

The context is still handed to the handler, because it is the part something can read rather
than someone. A placeholder nothing answers is left as it was: a message reading `Order  placed`
says less than one showing what was expected.

### How much is worth writing

```php
$logger = new Logger($handler, LogLevel::WARNING);

$logger->debug('not written');
$logger->warning('written');
```

Anything below the minimum is not passed on, which is how `debug` is turned off on a server
without taking the calls out of the code. `setMinimumLevel()` changes it later.

A level which is not one of the eight is refused with `Psr\Log\InvalidArgumentException` — the
one the specification names — rather than written as a level nothing can ever filter on.

### Where the entries go

```php
$logger
    ->setHandler(new StorageHandler('/var/log/app.log', new LocalStorage()))
    ->addHandler(new ConsoleHandler());
```

`setHandler()` replaces whatever was there; `addHandler()` puts one beside it, so a file and a
terminal both get the entry.

| Handler | Writes |
| --- | --- |
| `Handlers\StorageHandler` | through any [storage](https://github.com/quillstack/storage-interface) — a file, or wherever else one is implemented |
| `Handlers\ConsoleHandler` | where a person watching can see it, coloured by level |

A handler is one method, so somewhere else to write is one class:

```php
use Quillstack\Logger\HandlerInterface;

final class SyslogHandler implements HandlerInterface
{
    public function log(mixed $level, Stringable|string $message, array $context = []): bool
    {
        return syslog($this->priority($level), (string) $message);
    }
}
```

### In an application

The framework asks the container for `Psr\Log\LoggerInterface`, so pointing that at a logger
is all it takes:

```php
$app = new App(__DIR__ . '/../.env', [
    LoggerInterface::class => $logger,
]);
```

Anything asking for a logger then has one, and the error middleware writes to it.

### Technical documentation

| Class | What it is |
| --- | --- |
| `Logger` | the logger, implementing `Psr\Log\LoggerInterface` |
| `Level` | the eight levels and how they rank; `SEVERITY`, `name()`, `reaches()` |
| `Message` | puts the context into the message |
| `HandlerInterface` | `log(mixed $level, Stringable\|string $message, array $context = []): bool` |
| `Exceptions\UnknownLevelException` | a `Psr\Log\InvalidArgumentException` |
| `Exceptions\HandlerNotSetException` | there is nowhere to write |

### Unit tests

```shell
composer test
composer test:coverage
composer stan
```

### Docker

```shell
docker-compose up -d
docker exec -w /var/www/html -it quillstack_logger sh
```

### License

MIT. See [LICENSE](LICENSE).
