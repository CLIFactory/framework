<?php

namespace CLIFactory {
	
	use CLIFactory\Contracts\BaseConfigInterface;
	
	class BaseConfig implements BaseConfigInterface
	{
		/** @var string $name CLI Name */
		public string $name;
		
		/** @var string $version CLI Version */
		public string $version;
		
		/** @var array $commands Collection of commands */
		protected static array $commands = [];
		
		/**
		 * Returns the collection of $commands
		 *
		 * @return array
		 */
		public function commands(): array
		{
			return [...self::$commands, ...static::$commands];
		}
	}
}