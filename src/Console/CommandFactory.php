<?php

declare(strict_types=1);

namespace RandomPHP\Faro\Console;

use DI\DependencyException;
use DI\Invoker\FactoryParameterResolver;
use DI\Factory\RequestedEntry;
use Symfony\Component\Console\Command\Command;

/**
 * Class CommandFactory
 * @package RandomPHP\Faro\Console
 */
class CommandFactory
{
    /**
     * @var FactoryParameterResolver
     */
    private FactoryParameterResolver $resolver;

    public function __construct(FactoryParameterResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param RequestedEntry $entry
     * @return Command
     * @throws DependencyException
     */
    public function create(RequestedEntry $entry): Command
    {
        $name = $entry->getName();
        if (!is_subclass_of($name, Command::class)) {
            throw new DependencyException('"' . self::class . '" cannot instantiate class "' . $name . '".');
        }
        try {
            $args = $this->resolver->getParameters(
                new \ReflectionMethod($name, '__construct'),
                [],
                []
            );
            if (empty($args)) {
                return new $name();
            }
            return new $name(...$args);
        } catch (\ReflectionException $exception) {
            throw new DependencyException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}