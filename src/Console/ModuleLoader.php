<?php

declare(strict_types=1);

namespace RandomPHP\Faro\Console;

use RandomPHP\Faro\Exceptions\ModuleLoaderException;

final class ModuleLoader
{
    private static ?ModuleLoader $instance = null;

    /**
     * @return ModuleLoader
     */
    private static function getInstance(): ModuleLoader
    {
        if (!(static::$instance instanceof ModuleLoader)) {
            static::$instance = new ModuleLoader();
        }
        return static::$instance;
    }

    /**
     * @param string $moduleFqn
     * @return void
     * @throws ModuleLoaderException
     */
    public static function registerModule(string $moduleFqn): void
    {
        static::getInstance()->addModuleFqn($moduleFqn);
    }

    private array $modulesList = [];

    /**
     * ModuleLoader constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param string $moduleFqn
     * @return void
     * @throws ModuleLoaderException
     */
    public function addModuleFqn(string $moduleFqn): void
    {
        if (class_exists($moduleFqn, false)) {
            throw new ModuleLoaderException(
                '"' .
                $moduleFqn
                . '" is already loaded, you cannot provide already loaded classes to the ModuleLoader'
            );
        }
        if (!in_array($moduleFqn, $this->modulesList)) {
            $this->modulesList[] = $moduleFqn;
        }
    }

}