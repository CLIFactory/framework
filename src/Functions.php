<?php

use Psr\Log\LogLevel;
use CLIFactory\Libraries;
use CLIFactory\Bootstrap;
use CLIFactory\Contracts;
use CLIFactory\BaseConfig;

// If the function doesn't exist, let's create it!
if (!function_exists('dump')) {
    /**
     * Facade function that dumps debug data into
     * the viewport using the Dump library class.
     *
     * @param mixed $data
     *
     * @return void
     */
    function dump(mixed ...$data): void
    {
        new Libraries\Dump(...$data);
    }
}

// If the function doesn't exist, let's create it!
if (!function_exists('cli_factory')) {
    /**
     * Builds a new CLI application
     *
     * @param Contracts\BaseConfigInterface $config
     *
     * @return void
     */
    function cli_factory(Contracts\BaseConfigInterface $config): void
    {
        (new Bootstrap($config))->run();
    }
}


if (!function_exists('logger')) {
    function logger(
        string $message,
        string $level = LogLevel::DEBUG,
        string $channel = 'event',
        string $file = 'cli_factory.log'
    ) {
        (new Libraries\Logger($file, $channel))->$level($message);
    }
}
