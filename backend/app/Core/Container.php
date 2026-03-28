<?php

namespace App\Core;

use Exception;
use ReflectionClass;
use ReflectionParameter;

class Container
{
    protected static array $bindings = [];
    protected static array $instances = [];

    /**
     * Bind a resolver to a name.
     */
    public static function bind(string $name, callable|string $resolver, bool $singleton = false): void
    {
        self::$bindings[$name] = [
            'resolver' => $resolver,
            'singleton' => $singleton
        ];
    }

    /**
     * Shortcut for binding a singleton.
     */
    public static function singleton(string $name, callable|string $resolver): void
    {
        self::bind($name, $resolver, true);
    }

    /**
     * Resolve the service. Supports manual bindings and Auto-wiring.
     * @throws Exception
     */
    public static function resolve(string $name): mixed
    {
        // 1. Return existing singleton instance if available
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        // 2. If a manual binding exists, use its resolver
        if (isset(self::$bindings[$name])) {
            $binding = self::$bindings[$name];
            $resolver = $binding['resolver'];

            $instance = is_callable($resolver)
                ? $resolver()
                : self::autoWire($resolver);

            if ($binding['singleton']) {
                self::$instances[$name] = $instance;
            }

            return $instance;
        }

        // 3. Fallback to Auto-wiring (tries to instantiate the class name directly)
        return self::autoWire($name);
    }

    /**
     * Automatically instantiate a class and resolve its dependencies.
     * @throws Exception
     */
    private static function autoWire(string $class): mixed
    {
        if (!class_exists($class)) {
            throw new Exception("Service or Class [$class] cannot be resolved.");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class [$class] is not instantiable (it might be abstract).");
        }

        $constructor = $reflection->getConstructor();

        // No constructor? Just new it up.
        if (null === $constructor) {
            return new $class;
        }

        // Resolve constructor dependencies recursively
        $parameters = $constructor->getParameters();
        $dependencies = array_map(function (ReflectionParameter $param) use ($class) {
            $type = $param->getType();

            if (!$type || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    return $param->getDefaultValue();
                }
                throw new Exception("Cannot resolve parameter [{$param->getName()}] in [$class].");
            }

            // Recursive resolution: The container resolves the dependency's class name
            return self::resolve($type->getName());
        }, $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }
}