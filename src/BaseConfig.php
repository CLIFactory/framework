<?php

namespace CLIFactory {

    use CLIFactory\Contracts\BaseConfigInterface;

    class BaseConfig implements BaseConfigInterface
    {
        /** @var array $commands Collection of commands */
        protected static array $commands = [];

        /** @var string $name CLI Name */
        protected static string $name;

        /** @var string $version CLI Version */
        protected static string $version;

        /** @inheritdoc */
        public function commands(): array
        {
            return [...self::$commands, ...static::$commands];
        }

        /** @inheritdoc */
        public function name(): string
        {
            return static::$name;
        }

        /** @inheritdoc */
        public function version(): string
        {
            return static::$version;
        }
    }
}
