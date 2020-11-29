<?php

declare(strict_types=1);

namespace Sicet7\Faro;

use Psr\Container\ContainerInterface;

abstract class AbstractModule
{
    /**
     * Override this method to define custom definitions in the container.
     *
     * @return array
     */
    public static function getDefinitions(): array
    {
        return [];
    }

    /**
     * Override this method to interact with the container right after it is created.
     *
     * @param ContainerInterface $container
     */
    public static function setup(ContainerInterface $container): void
    {
        // Override this method to interact with the container right after it is created.
    }
}