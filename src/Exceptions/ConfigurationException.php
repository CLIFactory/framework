<?php

namespace CLIFactory\Exceptions;

use RuntimeException;

class ConfigurationException extends RuntimeException
{
    protected $message = 'There was an error with your configuration';
}
