<?php

namespace CLIFactory\Exceptions;

use CLIFactory\BaseConfig;

class ConfigurationException extends \RuntimeException
{
	protected $message = 'There was an error with your configuration';
}