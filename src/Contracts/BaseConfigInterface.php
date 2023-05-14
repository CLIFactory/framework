<?php

namespace CLIFactory\Contracts;

interface BaseConfigInterface
{
    /** @return string CLI Name */
    public function name(): string;

    /** @return string CLI Version */
    public function version(): string;

    /** @return array CLI Commands */
    public function commands(): array;
}
