<?php

namespace Sicet7\Faro;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Exception\ModuleLoaderException;

class ModuleLoader
{
    /**
     * @var string
     */
    private string $moduleFqn;

    /**
     * @var ModuleContainer
     */
    private ModuleContainer $moduleContainer;

    /**
     * @var bool
     */
    private bool $loaded = false;

    /**
     * @var bool
     */
    private bool $setup = false;

    /**
     * @var bool
     */
    private bool $enabled = false;

    /**
     * @var string[]
     */
    private array $dependencyNames = [];

    /**
     * @var ModuleLoader[]
     */
    private array $dependencyLoaders = [];

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array
     */
    private array $definitions = [];

    /**
     * ModuleLoader constructor.
     * @param string $moduleFqn
     * @param ModuleContainer $moduleContainer
     * @throws ModuleLoaderException
     */
    public function __construct(
        string $moduleFqn,
        ModuleContainer $moduleContainer
    ) {
        $this->moduleFqn = '\\' . ltrim($moduleFqn, '\\');
        $this->moduleContainer = $moduleContainer;
        $this->init();
    }

    /**
     * @throws ModuleLoaderException
     */
    private function init()
    {
        if (!is_subclass_of($this->getModuleFqn(), AbstractModule::class)) {
            throw new ModuleLoaderException(
                "Invalid module class. \"{$this->getModuleFqn()}\" must be an instance of " .
                '"' . AbstractModule::class . '".'
            );
        }

        $enabled = $this->moduleRead('isEnabled');
        if (!is_bool($enabled)) {
            throw new ModuleLoaderException(
                "Invalid \"isEnabled\" state on module: \"{$this->getModuleFqn()}\""
            );
        }
        $this->enabled = $enabled;

        /* F*** the rest if we ain't enabled :-) */
        if (!$this->isEnabled()) {
            return;
        }

        $dependencies = $this->moduleRead('getDependencies');
        if (!is_array($dependencies)) {
            throw new ModuleLoaderException(
                "Unknown dependency type on module: \"{$this->getModuleFqn()}\""
            );
        }
        $this->dependencyNames = $dependencies;

        $name = $this->moduleRead('getName');
        if (!is_string($name) || empty($name)) {
            throw new ModuleLoaderException(
                "The module name of {$this->getModuleFqn()} must be a non empty string value."
            );
        }
        $this->name = $name;

        $definitions = $this->moduleRead('getDefinitions');
        if (!is_array($definitions)) {
            throw new ModuleLoaderException(
                "Invalid definitions type on module: \"{$this->getModuleFqn()}\"."
            );
        }
        $this->definitions = $definitions;
    }

    /**
     * @param string $method
     * @return mixed
     */
    private function moduleRead(string $method)
    {
        return call_user_func([$this->getModuleFqn(), $method]);
    }

    /**
     * @return bool
     */
    private function findDependencyModuleLoaders(): bool
    {
        if (empty($this->dependencyNames)) {
            return true;
        }

        foreach ($this->moduleContainer->getList() as $moduleLoader) {
            if (!isset($this->dependencyLoaders[$moduleLoader->getName()]) &&
                in_array($moduleLoader->getName(), $this->dependencyNames)) {
                $this->dependencyLoaders[$moduleLoader->getName()] = $moduleLoader;
            }
        }

        if (count($this->dependencyNames) == count($this->dependencyLoaders)) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function isDependenciesLoaded(): bool
    {
        if (empty($this->dependencyNames)) {
            return true;
        }
        if (!$this->findDependencyModuleLoaders()) {
            return false;
        }
        foreach ($this->dependencyLoaders as $loader) {
            if (!$loader->isEnabled() || !$loader->isLoaded()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    private function isDependenciesSetup(): bool
    {
        if (empty($this->dependencyNames)) {
            return true;
        }
        if (!$this->findDependencyModuleLoaders()) {
            return false;
        }
        foreach ($this->dependencyLoaders as $loader) {
            if (!$loader->isEnabled() || !$loader->isSetup()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return ModuleContainer
     */
    protected function getModuleContainer(): ModuleContainer
    {
        return $this->moduleContainer;
    }

    /**
     * @return string
     */
    public function getModuleFqn(): string
    {
        return $this->moduleFqn;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @return bool
     */
    public function isSetup(): bool
    {
        return $this->setup;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return bool
     */
    public function load(): bool
    {
        if ($this->isLoaded() || !$this->isEnabled() || !$this->isDependenciesLoaded()) {
            return false;
        }
        $this->getModuleContainer()
            ->getContainerBuilder()
            ->addDefinitions($this->getDefinitions());
        $this->loaded = true;
        return true;
    }

    /**
     * @param ContainerInterface $container
     * @return bool
     */
    public function setup(ContainerInterface $container): bool
    {
        if ($this->isSetup() || !$this->isEnabled() || !$this->isDependenciesSetup()) {
            return false;
        }
        call_user_func([$this->getModuleFqn(), 'setup'], $container);
        $this->setup = true;
        return true;
    }
}