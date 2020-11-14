<?php

declare(strict_types=1);

namespace Sicet7\Faro\Console;

use DI\Container;
use DI\ContainerBuilder;
use DI\Invoker\FactoryParameterResolver;
use Psr\Container\ContainerInterface;
use Sicet7\Faro\Exceptions\ModuleLoaderException;
use Sicet7\Faro\ModuleLoader as AbstractModuleLoader;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

use function DI\create;
use function DI\factory;
use function DI\get;

final class ModuleLoader extends AbstractModuleLoader
{
    /**
     * @return Application
     * @throws ModuleLoaderException
     */
    public static function getApplication(): Application
    {
        return static::getInstance()->buildApplication();
    }

    private ?ContainerInterface $container = null;

    private function getContainerBuilder(): ContainerBuilder
    {
        return new ContainerBuilder(Container::class);
    }

    /**
     * @return array
     * @throws ModuleLoaderException
     */
    private function getCommandsArray(): array
    {
        $commandMasterArray = [];
        foreach ($this->getList() as $moduleFqn) {
            $commandArray = call_user_func([$moduleFqn, 'getCommands']);
            if (!is_array($commandArray)) {
                throw new ModuleLoaderException(
                    'Failed to load command from "' . $moduleFqn . '" Module,' .
                    'type of returned value was not "array".'
                );
            }
            foreach ($commandArray as $commandName => $commandClassFqn) {
                $commandMasterArray[$commandName] = $commandClassFqn;
            }
        }
        return $commandMasterArray;
    }

    /**
     * @return ContainerInterface
     * @throws \Exception
     */
    private function buildContainer(): ContainerInterface
    {
        $cb = $this->getContainerBuilder();

        $cb->useAutowiring(false);
        $cb->useAnnotations(false);

        $commandsArray = $this->getCommandsArray();

        $cb->addDefinitions([
            FactoryParameterResolver::class =>
                create(FactoryParameterResolver::class)
                    ->constructor(get(ContainerInterface::class)),
            CommandFactory::class => create(CommandFactory::class)
                ->constructor(get(FactoryParameterResolver::class)),
            CommandLoaderInterface::class => create(ContainerCommandLoader::class)
                ->constructor(get(ContainerInterface::class), $commandsArray),
        ]);

        $commandDefinitions = [];
        foreach ($commandsArray as $commandClassFqn) {
            $commandDefinitions[$commandClassFqn] = factory([CommandFactory::class, 'create']);
        }
        $cb->addDefinitions($commandDefinitions);

        foreach ($this->getList() as $moduleFqn) {
            $moduleDefinitions = call_user_func([$moduleFqn, 'getDefinitions']);
            if (!is_array($moduleDefinitions)) {
                throw new ModuleLoaderException(
                    'Invalid definitions type. Method "getDefinitions" must return array type.' .
                    'Module: "' . $moduleFqn . '".'
                );
            }
            $cb->addDefinitions($moduleDefinitions);
        }

        return $cb->build();
    }

    private function getContainer(): ContainerInterface
    {
        if (!($this->container instanceof ContainerInterface)) {
            $this->container = $this->buildContainer();
            foreach ($this->getList() as $moduleFqn) {
                call_user_func([$moduleFqn, 'postContainerSetup'], $this->container);
            }
        }
        return $this->container;
    }

    /**
     * @return Application
     * @throws ModuleLoaderException
     */
    public function buildApplication(): Application
    {
        foreach ($this->getList() as $fqn) {
            if (!is_subclass_of($fqn, ModuleInterface::class)) {
                throw new ModuleLoaderException(
                    '"' . $fqn . '" is not a subclass of "' . ModuleInterface::class . '", ' .
                    'this is required for the it to be a valid module.'
                );
            }
        }

        $application = new Application();
        $application->setCommandLoader($this->getContainer()->get(CommandLoaderInterface::class));
        return $application;
    }
}