<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

interface HasCommandsInterface
{
    /**
     * Should return an array where the keys is the command name
     * and the value is the FQN of the commands class.
     *
     * @return array
     */
    public static function getCommands(): array;
}