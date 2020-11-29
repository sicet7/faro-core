<?php

declare(strict_types=1);

namespace Sicet7\Faro;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Exception\ModuleLoaderException;

abstract class ModuleLoader
{
    private static ?ModuleLoader $instance = null;

    /**
     * @return ModuleLoader
     */
    public final static function getInstance(): ModuleLoader
    {
        if (!(static::$instance instanceof ModuleLoader)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param string $moduleFqn
     */
    public final static function registerModule(string $moduleFqn): void
    {
        static::getInstance()->addModule($moduleFqn);
    }

    /**
     * @return array
     */
    public final static function getModuleList(): array
    {
        return static::getInstance()->getList();
    }

    private array $list = [];

    /**
     * ModuleLoader constructor.
     */
    protected function __construct() { }

    /**
     * @param string $moduleFqn
     */
    public final function addModule(string $moduleFqn): void
    {
        $this->list[] = $moduleFqn;
    }

    /**
     * @return array
     */
    public final function getList(): array
    {
        return $this->list;
    }

    /**
     * @return ContainerInterface
     * @throws ModuleLoaderException
     * @throws \Exception
     */
    public final function buildContainer(): ContainerInterface
    {
        $moduleList = $this->getList();

        $containerBuilder = new ContainerBuilder(Container::class);
        $containerBuilder->useAutowiring(false);
        $containerBuilder->useAnnotations(false);

        $extensionDefinitions = $this->definitions();
        if (!empty($extensionDefinitions)) {
            $containerBuilder->addDefinitions($extensionDefinitions);
        }

        foreach ($moduleList as $moduleFqn) {
            if (!is_subclass_of($moduleFqn, ModuleInterface::class)) {
                throw new ModuleLoaderException(
                    'Invalid module class. "' . $moduleFqn . '" must be an instance of ' .
                    '"' . ModuleInterface::class . '".'
                );
            }

            $moduleDefinitions = call_user_func([$moduleFqn, 'getDefinitions']);
            if (!is_array($moduleDefinitions)) {
                throw new ModuleLoaderException(
                    'Invalid definitions type. Method "getDefinitions" must return array type. ' .
                    'Module: "' . $moduleFqn . '".'
                );
            }
            $containerBuilder->addDefinitions($moduleDefinitions);
        }

        $container = $containerBuilder->build();

        foreach ($moduleList as $moduleFqn) {
            call_user_func([$moduleFqn, 'setup'], $container);
        }
        return $container;
    }

    /**
     * This function is to allow sub classes to define their custom definitions
     *
     * @return array
     */
    public abstract function definitions(): array;
}