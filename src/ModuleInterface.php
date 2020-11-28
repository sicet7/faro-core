<?php

declare(strict_types=1);

namespace Sicet7\Faro;

use Psr\Container\ContainerInterface;

interface ModuleInterface
{
    /**
     * This should return an definitions array that PHP-DI can use the build the container.
     * @see https://php-di.org/doc/php-definitions.html
     *
     * @return array
     */
    public static function getDefinitions(): array;

    /**
     * This is to allow interaction with classes after the container has been build.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void;
}