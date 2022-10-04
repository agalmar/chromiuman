<?php

namespace Codeception\Extension;

/**
 * Chromiuman.
 *
 * The Codeception extension for automatically starting and stopping Chromedriver
 * when running tests.
 *
 * Originally based off of PhpBuiltinServer Codeception extension
 * https://github.com/tiger-seo/PhpBuiltinServer
 * and Grantlucas Phantoman
 * https://github.com/grantlucas/phantoman
 */

use Codeception\Event\SuiteEvent;
use Codeception\Events;
use Codeception\Exception\ExtensionException;
use Codeception\Extension;

/**
 * Class Chromiuman.
 *
 * @package Codeception\Extension
 */
class Chromiuman extends Extension
{

    /**
     * Events to listen to.
     *
     * @var array
     */
    protected static $events = [
        Events::SUITE_INIT => 'suiteInit',
    ];


    /**
     * A resource representing the Chromedriver process.
     *
     * @var resource
     */
    private $resource;

    /**
     * File pointers that correspond to PHP's end of any pipes that are created.
     *
     * @var array
     */
    private $pipes;

    /**
     * Chromiuman constructor.
     *
     * @param array $config  Current extension configuration.
     * @param array $options Passed running options.
     *
     * @throws \Codeception\Exception\ExtensionException
     */
    public function __construct(array $config, array $options)
    {
        // Codeception has an option called silent, which suppresses the console
        // output. Unfortunately there is no builtin way to activate this mode for
        // a single extension. This is why the option will passed from the
        // extension configuration ($config) to the global configuration ($options);
        // Note: This must be done before calling the parent constructor.
        if (isset($config['silent']) && $config['silent']) {
            $options['silent'] = true;
        }
        parent::__construct($config, $options);

        // Set default path for Chromedriver to "vendor/bin/chromedriver" for if it was
        // installed via composer.
        if (!isset($this->config['path'])) {
            $this->config['path'] = 'vendor/bin/chromedriver';
        }

        // Add .exe extension if running on the windows.
        if ($this->isWindows() && file_exists(realpath($this->config['path'] . '.exe'))) {
            $this->config['path'] .= '.exe';
        }

        if (!file_exists(realpath($this->config['path']))) {
            throw new ExtensionException($this, 'Chromedriver executable not found: ' . realpath($this->config['path']));
        }

        // Set default Chromedriver port.
        if (!isset($this->config['port'])) {
            $this->config['port'] = 9515;
        }

        //set default url-base.
        if (!isset($this->config['url_base'])) {
            $this->config['url_base'] = '/wd/hub';
        }

        // Set default debug mode.
        if (!isset($this->config['debug'])) {
            $this->config['debug'] = false;
        }
    }

    /**
     * Stop the server when we get destroyed.
     */
    public function __destruct()
    {
        $this->stopChromedriver();
    }

    /**
     * Start Chromedriver.
     *
     * @throws \Codeception\Exception\ExtensionException
     */
    private function startChromedriver()
    {
        if ($this->resource !== null) {
            return;
        }

        $this->writeln(PHP_EOL);
        $this->writeln('Starting Chromedriver.');

        $command = $this->getCommand();

        if ($this->config['debug']) {
            $this->writeln(PHP_EOL);

            // Output the generated command.
            $this->writeln('Generated Chromedriver Command:');
            $this->writeln($command);
            $this->writeln(PHP_EOL);
        }

        $descriptorSpec = [
            ['pipe', 'r'],
            ['file', $this->getLogDir() . 'chromedriver.output.txt', 'w'],
            ['file', $this->getLogDir() . 'chromedriver.errors.txt', 'a'],
        ];

        $this->resource = proc_open($command, $descriptorSpec, $this->pipes, null, null, ['bypass_shell' => true]);

        if (!is_resource($this->resource) || !proc_get_status($this->resource)['running']) {
            proc_close($this->resource);
            throw new ExtensionException($this, 'Failed to start Chromedriver.');
        }

        // Wait till the server is reachable before continuing.
        $max_checks = 10;
        $checks = 0;

        $this->write('Waiting for Chromedriver to be reachable.');
        while (true) {
            if ($checks >= $max_checks) {
                throw new ExtensionException($this, 'Chromedriver never became reachable.');
            }

            $fp = @fsockopen('127.0.0.1', $this->config['port'], $errCode, $errStr, 10);
            if ($fp) {
                $this->writeln('');
                $this->writeln('Chromedriver now accessible.');
                fclose($fp);
                break;
            }

            $this->write('.');
            $checks++;

            // Wait before checking again.
            sleep(1);
        }

        // Clear progress line writing.
        $this->writeln('');
    }

    /**
     * Stop Chromedriver.
     */
    private function stopChromedriver()
    {
        if ($this->resource !== null) {
            $this->write('Stopping Chromedriver.');

            // Wait till the server has been stopped.
            $max_checks = 10;
            for ($i = 0; $i < $max_checks; $i++) {
                // If we're on the last loop, and it's still not shut down, just
                // unset resource to allow the tests to finish.
                if ($i === $max_checks - 1 && proc_get_status($this->resource)['running'] === true) {
                    $this->writeln('');
                    $this->writeln('Unable to properly shutdown Chromedriver.');
                    unset($this->resource);
                    break;
                }

                // Check if the process has stopped yet.
                if (proc_get_status($this->resource)['running'] === false) {
                    $this->writeln('');
                    $this->writeln('Chromedriver stopped.');
                    unset($this->resource);
                    break;
                }

                foreach ($this->pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }

                // Terminate the process.
                // Note: Use of SIGINT adds dependency on PCTNL extension so we
                // use the integer value instead.
                proc_terminate($this->resource, 2);

                $this->write('.');

                // Wait before checking again.
                sleep(1);
            }
        }
    }

    /**
     * Build the parameters for our command.
     *
     * @return string
     *   All parameters separated by spaces.
     */
    private function getCommandParameters()
    {
        // Map our config options to Chromedriver options.
        $mapping = [
            'port' => '--port',
            'adb_port' => '--adb-port',
            'log_path' => '--log-path',
            'log_level' => '--log-level',
            'debug' => '--verbose',
            'silent' => '--silent',
            'append_log' => '--append-log',
            'url_base' => '--url-base',
            'replayable' => '--replayable',
            'enable_chrome_logs' => '--enable-chrome-logs',
            'allowed_ips' => '--allowed-ips',
            'allowed_origins' => '--allowed-origins',
            'disable_dev_shm_usage' => '--disable-dev-shm-usage',
            'readable_timestamp' => '--readable-timestamp'
        ];

        $params = [];
        foreach ($this->config as $configKey => $configValue) {
            if ((isset($mapping[$configKey])) && (!empty($mapping[$configKey]))) {
                if ($configValue === true) {
                    $params[] = $mapping[$configKey];
                } elseif ($configValue !== false) {
                    $params[] = $mapping[$configKey] . '=' . $configValue;
                }
            }
        }
        return implode(' ', $params);
    }

    /**
     * Get Chromedriver command.
     *
     * @return string Command to execute.
     */
    private function getCommand()
    {
        // Prefix command with exec on non Windows systems to ensure that we receive the correct pid.
        // See http://php.net/manual/en/function.proc-get-status.php#93382
        $commandPrefix = $this->isWindows() ? '' : 'exec ';
        return $commandPrefix . escapeshellarg(realpath($this->config['path'])) . ' ' . $this->getCommandParameters();
    }

    /**
     * Checks if the current machine is Windows.
     *
     * @return bool
     *   True if the machine is windows.
     */
    private function isWindows()
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }

    /**
     * Suite Init.
     *
     * @param SuiteEvent $e The event with suite, result and settings.
     *
     * @throws ExtensionException
     */
    public function suiteInit(SuiteEvent $e)
    {
        // Check if Chromedriver should only be started for specific suites.
        if (isset($this->config['suites'])) {
            if (is_string($this->config['suites'])) {
                $suites = [$this->config['suites']];
            } else {
                $suites = $this->config['suites'];
            }

            // If the current suites aren't in the desired array, return without
            // starting Chromedriver.
            if (!in_array($e->getSuite()->getBaseName(), $suites, true)
                && !in_array($e->getSuite()->getName(), $suites, true)) {
                return;
            }
        }

        // Start Chromedriver.
        $this->startChromedriver();
    }
}
