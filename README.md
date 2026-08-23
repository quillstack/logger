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

## Why this exists

PSR-3 is eight methods and an interface. Monolog is the answer everybody uses, and it is a good
one — but it is 34 files and a formatter-and-processor architecture for what an API usually
needs, which is a line in a file saying what happened.

This is that: a level, a message, whatever was passed alongside, and a handler that is one
method. **Placeholders are filled in**, which PSR-3 §1.2 describes and which Monolog leaves to a
processor you have to add. Where you want somewhere else to write, a handler is a class with a
`log()` method and nothing else.

It was written to take the last third-party implementation out of this framework. Everything
else here already spoke to interfaces; the logger was the one place a concrete outside package
was still doing the work.

## Requirements

- PHP 8.1 or newer

## Installation

```shell
composer require quillstack/logger
```

## Usage

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
| `Handlers\FileHandler` | to a local file, keeping it open — the fastest way, and the usual one |
| `Handlers\StorageHandler` | through any [storage](https://github.com/quillstack/storage-interface) — anywhere one is implemented |
| `Handlers\ConsoleHandler` | where a person watching can see it, coloured by level |

```php
$logger->setHandler(new FileHandler('/var/log/app.log'));
```

Use `StorageHandler` where entries go somewhere that is not a local file; `FileHandler` is nine
times faster where they do, and the [benchmark](#benchmark) says why.

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

## Technical documentation

| Class | What it is |
| --- | --- |
| `Logger` | the logger, implementing `Psr\Log\LoggerInterface` |
| `Level` | the eight levels and how they rank; `SEVERITY`, `name()`, `reaches()` |
| `Message` | puts the context into the message |
| `HandlerInterface` | `log(mixed $level, Stringable\|string $message, array $context = []): bool` |
| `Exceptions\UnknownLevelException` | a `Psr\Log\InvalidArgumentException` |
| `Exceptions\HandlerNotSetException` | there is nowhere to write |

## Benchmark

Measured with [quillstack/benchmark](https://github.com/quillstack/benchmark) on a thousand
entries written to a file, each with two placeholders and a context of two values. All of them
write the same thousand lines. Runs are interleaved and unconcurrent, each figure is the median
of five, and PHP is 8.5.7.

| | Version |
| --- | --- |
| quillstack/logger | v0.8.0 |
| monolog/monolog | 3.10.0 |

| | Per entry | Relative |
| --- | --- | --- |
| **`FileHandler`** | **2.51 µs** | — |
| `FileHandler`, with locking | 3.19 µs | 1.3× |
| monolog/monolog | 7.69 µs | 3.1× |
| monolog/monolog, with locking | 8.36 µs | 3.3× |
| `StorageHandler` | 22.37 µs | 8.9× |

Two things in that table are worth reading rather than skipping.

**The last row is this package's other handler**, and the nine-fold difference is the whole
reason `FileHandler` exists. `StorageHandler` writes through
[quillstack/storage-interface](https://github.com/quillstack/storage-interface), which knows how
to put contents at a path and nothing about a handle held open — so every entry is an open, a
lock, a write and a close. That is worth paying where entries go somewhere that is not a local
file. Where they go to a local file, it is not.

**And the gap to Monolog is not cleverness.** Monolog builds a `LogRecord`, runs it through
whatever processors are registered, and formats it with a configurable format string; this
concatenates a date, a level and a message. Three times the speed is what a fixed line format
costs Monolog to be configurable, and if you want a different one, Monolog is where you get it.

Locking is optional here because the file is opened for appending, and on a local filesystem the
kernel makes a single small append atomic. Where that does not hold — a file on NFS — pass
`locking: true` and pay the two thirds of a microsecond.

## Tests

```shell
composer test
composer test:coverage
composer stan
```

## The rest of Quillstack

This is one component of [Quillstack](https://github.com/quillstack), a PHP framework which is
as simple to use as it is strict about what it does.

- [quillstack/storage-interface](https://github.com/quillstack/storage-interface) — where entries are written through
- [quillstack/local-storage](https://github.com/quillstack/local-storage) — the implementation that writes files
- [quillstack/output](https://github.com/quillstack/output) — what colours the console handler
- [quillstack/framework](https://github.com/quillstack/framework) — where a logger is wired in

## License

MIT. See [LICENSE](LICENSE).
