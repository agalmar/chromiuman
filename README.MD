# Chromiuman

[![Latest Version](https://img.shields.io/packagist/v/agalmar/chromiuman.svg?style=flat-square)](https://packagist.org/packages/agalmar/chromiuman)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/agalmar/chromiuman.svg?style=flat-square)](https://packagist.org/packages/agalmar/chromiuman)


The [Codeception](http://codeception.com/) extension for automatically starting
and stopping [Chromedriver](https://sites.google.com/a/chromium.org/chromedriver/) when running tests.

## Minimum Requirements

- Codeception 2.2.7
- PHP 5.4

## Installation using [Composer](https://getcomposer.org)

```bash
$ composer require agalmar/chromiuman
```

Be sure to enable the extension in `codeception.yml` as shown in
[configuration](#configuration) below.

## Recommended Additional Packages

### Chromedriver Installer via Composer

It is highly recommended that you use the [Chromedriver
Installer](https://github.com/lbaey/chromedriver) package which will
install Chromedriver locally to your project in `vendor/bin`. Please follow the
[installation
instructions](https://github.com/lbaey/chromedriver#chrome-driver-packaged-in-composer)
provided.

**Chromiuman uses `vendor/bin/chromedriver` by default. If any other installation of
chromedriver is used, please set the path as shown in the configuration below.**

## Configuration

By default Chromiuman will use the path `vendor/bin/chromedriver` and port `9515`.

Enabling and configuration can be done in `codeception.yml` or in your suite config file.

### Enabling Chromedriver with defaults

```yaml
extensions:
    enabled:
        - Codeception\Extension\Chromiuman
```

### Enabling Chromiuman with alternate settings

```yaml
extensions:
    enabled:
        - Codeception\Extension\Chromiuman
    config:
        Codeception\Extension\Chromiuman:
            path: '/usr/bin/chromedriver'
            port: 9515
            suites: ['acceptance']
```

### Enabling Chromiuman in the acceptance suite except on the `ci` environment
```yaml
extensions:
  enabled:
    - Codeception\Extension\Chromiuman:
        suites: ['acceptance']
env:
  ci:
    extensions:
      enabled:
        - Codeception\Extension\Chromiuman:
            suites: []
```

### Available options

Options set in the Chromiuman configuration are mapped to [Chromedriver Options](
https://sites.google.com/a/chromium.org/chromedriver/capabilities#TOC-ChromeDriver-server-command-line-arguments).
The currently supported options are listed below.

- `path: {path}`
    - Full path to the Chromedriver binary.
    - Default: `vendor/bin/chromedriver`
- `port: {port}`
    - Webdriver port to start chromedriver with
    - Default: `9515`
- `adb_port: {port}`
    - Adb server port
- `log_path: {FILE}`
    - Write server log to file instead of stderr, increases log level to INFO
- `log_level: {LEVEL}`
    - Set log level: ALL, DEBUG, INFO, WARNING, SEVERE, OFF
- `debug: {true|false}`
    - Display debug output (log verbosely, equivalent to `log-level: {ALL}`) while Chromedriver runs
    - Default: `false`
- `silent: {true|false}`
    - Logs nothing (equivalent to `log-level: {OFF}`)
    - Default: `false`
- `append_log: {true|false}`
    - Append log file instead of rewriting
    - Default: `false`
- `url_base: {base URL}`
    - Base URL path prefix for commands, e.g. wd/url
    - Default: `wd/url`
- `replayable: {true|false}`
    - (experimental) log verbosely and don't truncate long strings so that the log can be replayed
- `enable_chrome_logs: {true|false}`
    - show logs from the browser (overrides other logging options)
- `allowed_ips: {CS-IP_List}`
    - Comma-separated allowlist of remote IP addresses which are allowed to connect to ChromeDriver
- `allowed_origins: {CS-Request_Origin_List}`
    - comma-separated allowlist of request origins which are allowed to connect to ChromeDriver. Using `*` to allow any
      host origin is dangerous!
- `disable_dev_shm_usage: {true|false}`
    - Do not use /dev/shm (add this switch if seeing errors related to shared memory)
    - Default: `false`
- `readable_timestamp: {true|false}`
    - Adds readable timestamps to log
    - Default: `false`
- `suites: {array|string}`
    - If omitted, Chromedriver is started for all suites
    - Specify an array of suites or a single suite name

## Usage

Once installed and enabled, running your tests with `php codecept run` will
automatically start the Chromedriver server and wait for it to be accessible before
proceeding with the tests.

```bash
Starting Chromedriver.
Waiting for Chromedriver to be reachable..
Chromedriver now accessible.
```

Once the tests are complete, Chromedriver will be shut down.

```bash
Stopping Chromedriver.
```
