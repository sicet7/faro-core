<?php

declare(strict_types=1);

namespace RandomPHP\Faro\Console;

use Psr\Container\ContainerInterface;

interface ModuleInterface
{

    /**
     * Should return an array where the keys is the command name
     * and the value is the FQN of the commands class.
     *
     * @return array
     */
    public static function getCommands(): array;

    /**
     * This should return an definitions array that PHP-DI can use the build the container.
     * @see https://php-di.org/doc/php-definitions.html
     *
     * @return array
     */
    public static function getDefinitions(): array;

    /**
     * This is to allow setup of classes after the container has been build.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function postContainerSetup(ContainerInterface $container): void;
}