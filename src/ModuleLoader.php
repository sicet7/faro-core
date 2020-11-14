<?php

declare(strict_types=1);

namespace RandomPHP\Faro;

use Psr\Container\ContainerInterface;

abstract class ModuleLoader
{
    private static ?ModuleLoader $instance = null;

    public static function getInstance(): ModuleLoader
    {
        if (!(static::$instance instanceof ModuleLoader)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function registerModule(string $moduleFqn): void
    {
        static::getInstance()->addModule($moduleFqn);
    }

    public static function getModuleList(): array
    {
        return static::getInstance()->getList();
    }

    private ?array $list = null;

    private function __construct()
    {
        $this->list = [];
    }

    public function addModule(string $moduleFqn): void
    {
        $this->list[] = $moduleFqn;
    }

    public function getList(): array
    {
        return $this->list;
    }

    abstract public function buildApplication();
}