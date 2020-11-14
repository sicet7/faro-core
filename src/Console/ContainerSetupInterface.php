<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

use Psr\Container\ContainerInterface;

interface ContainerSetupInterface
{

    /**
     * This is to allow setup of classes after the container has been build.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function setup(ContainerInterface $container): void;
}