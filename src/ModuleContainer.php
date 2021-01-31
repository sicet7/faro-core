<?php

namespace Sicet7\Faro;

use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Exception\ModuleLoaderException;

class ModuleContainer
{
    private static ?ModuleContainer $instance = null;

    /**
     * @return ModuleContainer
     */
    public final static function getInstance(): ModuleContainer
    {
        if (!(static::$instance instanceof ModuleContainer)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param string $moduleFqn
     * @throws ModuleLoaderException
     */
    public final static function registerModule(string $moduleFqn): void
    {
        static::getInstance()->addModule($moduleFqn);
    }

    /**
     * @return string[]
     */
    public final static function getModuleList(): array
    {
        return static::getInstance()->getList();
    }

    /**
     * Should be a class which the ContainerBuilder
     *
     * @var string
     */
    protected string $containerClass = Container::class;

    /**
     * @var ContainerBuilder
     */
    private ContainerBuilder $containerBuilder;

    /**
     * @var ModuleLoader[]
     */
    private array $moduleList = [];

    public function __construct()
    {
        $this->containerBuilder = new ContainerBuilder($this->containerClass);
        $this->containerBuilder->useAutowiring(false);
        $this->containerBuilder->useAnnotations(false);
        $extensionDefinitions = $this->definitions();
        if (!empty($extensionDefinitions)) {
            $this->containerBuilder->addDefinitions($extensionDefinitions);
        }
    }

    /**
     * @return ContainerBuilder
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        return $this->containerBuilder;
    }

    /**
     * @throws ModuleLoaderException
     */
    protected function loadDefinitions()
    {
        do {
            $loadCount = 0;
            foreach ($this->getList() as $loader) {
                if (!$loader->isEnabled()) {
                    continue;
                }

                if (!$loader->isLoaded() && $loader->load()) {
                    $loadCount++;
                }
            }
        } while($loadCount !== 0);

        foreach ($this->getList() as $loader) {
            if ($loader->isEnabled() && !$loader->isLoaded()){
                throw new ModuleLoaderException("Failed to load module: {$loader->getModuleFqn()}");
            }
        }
    }

    /**
     * @param ContainerInterface $container
     * @throws ModuleLoaderException
     */
    protected function setupModules(ContainerInterface $container)
    {
        do {
            $setupCount = 0;
            foreach ($this->getList() as $loader) {
                if (!$loader->isEnabled()) {
                    continue;
                }
                if (!$loader->isSetup() && $loader->setup($container)) {
                    $setupCount++;
                }
            }
        } while($setupCount !== 0);

        foreach ($this->getList() as $loader) {
            if ($loader->isEnabled() && !$loader->isSetup()) {
                throw new ModuleLoaderException("Module setup failed for module: {$loader->getModuleFqn()}");
            }
        }
    }

    /**
     * @param string $moduleFqn
     * @throws ModuleLoaderException
     */
    public function addModule(string $moduleFqn): void
    {
        $moduleLoader = $this->createLoader($moduleFqn);
        if ($moduleLoader->isEnabled()) {
            $this->moduleList[$moduleLoader->getName()] = $moduleLoader;
        }
    }

    /**
     * @return ModuleLoader[]
     */
    public function getList(): array
    {
        return $this->moduleList;
    }

    /**
     * @return ContainerInterface
     * @throws ModuleLoaderException
     */
    public function buildContainer(): ContainerInterface
    {
        try {
            $this->loadDefinitions();
            $container = $this->getContainerBuilder()->build();
            $this->setupModules($container);
            return $container;
        } catch (\Exception $exception) {
            throw new ModuleLoaderException($exception, $exception->getCode(), $exception);
        }
    }

    /**
     * Creates the loader for the modules.
     *
     * @param string $moduleFqn
     * @return ModuleLoader
     * @throws ModuleLoaderException
     */
    protected function createLoader(string $moduleFqn): ModuleLoader
    {
        return new ModuleLoader($moduleFqn, $this);
    }


    /**
     * This function is to allow sub classes to define their custom definitions
     *
     * @return array
     */
    protected function definitions(): array
    {
        return [];
    }
}