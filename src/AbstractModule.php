<?php

declare(strict_types=1);

namespace Sicet7\Faro;

use Psr\Container\ContainerInterface;

abstract class AbstractModule
{
    /**
     * Should return the name of the module.
     *
     * @return string
     */
    abstract public static function getName(): string;

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

    /**
     * Returns an array containing names of modules that should be loaded before loading this module.
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Returns a bool value indicating whether or not the module is enabled.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return true;
    }
}