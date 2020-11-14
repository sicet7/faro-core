<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

interface HasDefinitionsInterface
{
    /**
     * This should return an definitions array that PHP-DI can use the build the container.
     * @see https://php-di.org/doc/php-definitions.html
     *
     * @return array
     */
    public static function getDefinitions(): array;
}