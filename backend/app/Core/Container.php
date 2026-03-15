<?php

namespace App\Core;

class Container
{
    protected static $bindings = [];
    protected static $instances = [];

    public static function bind($name, $resolver, $singleton = false)
    {
        self::$bindings[$name] = ['resolver' => $resolver, 'singleton' => $singleton];
    }

    public static function resolve($name)
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        if (!isset(self::$bindings[$name])) {
            throw new \Exception("Service not found: $name");
        }

        $binding = self::$bindings[$name];
        $instance = call_user_func($binding['resolver']);

        if ($binding['singleton']) {
            self::$instances[$name] = $instance;
        }

        return $instance;
    }
}