# Codeception Progress Reporter

[![CI](https://github.com/fr05t1k/codeception-progress-reporter/actions/workflows/ci.yml/badge.svg)](https://github.com/fr05t1k/codeception-progress-reporter/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/codeception/codeception-progress-reporter/v)](https://packagist.org/packages/codeception/codeception-progress-reporter)
[![Total Downloads](https://poser.pugx.org/codeception/codeception-progress-reporter/downloads)](https://packagist.org/packages/codeception/codeception-progress-reporter)
[![PHP Version Require](https://poser.pugx.org/codeception/codeception-progress-reporter/require/php)](https://packagist.org/packages/codeception/codeception-progress-reporter)
[![License](https://poser.pugx.org/codeception/codeception-progress-reporter/license)](https://packagist.org/packages/codeception/codeception-progress-reporter)

A [Codeception](https://codeception.com/) extension that replaces the default
test output with a single, live terminal **progress bar**. It shows the current
suite and test, running counts of successes/errors/fails, elapsed and estimated
time, and memory usage — handy for large suites where the default output scrolls
off the screen.

![preview](preview.svg)

## Features

- Live progress bar with success / error / fail counters.
- Current suite and test name displayed as the run advances.
- Elapsed vs. estimated time and peak memory usage.
- Failures are still printed in full at the end of the run.
- Automatically disables itself when running with `--steps` or `--debug`.

## Requirements

| Package version | PHP        | Codeception |
|-----------------|------------|-------------|
| `^5.0`          | `>= 8.1`   | `^5.0`      |
| `^4.0`          | `>= 5.6`   | `>= 2.3`    |

## Installation

```bash
composer require --dev codeception/codeception-progress-reporter
```

## Usage

Enable it globally in your `codeception.yml`:

```yaml
extensions:
    enabled:
        - Codeception\ProgressReporter\ProgressReporter
```

Or enable it for a single run:

```bash
codecept run --ext "Codeception\\ProgressReporter\\ProgressReporter"
```

## Contributing

Issues and pull requests are welcome. Please make sure the test suite and static
analysis pass before opening a PR:

```bash
composer install
vendor/bin/codecept run
vendor/bin/phpstan analyse
```

## License

Released under the [MIT License](LICENSE).
