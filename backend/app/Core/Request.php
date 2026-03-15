<?php

namespace App\Core;

class Request
{
    protected static $context = [];

    public static function body()
    {
        return json_decode(file_get_contents("php://input"), true);
    }

    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function uri()
    {
        return rtrim(strtok($_SERVER["REQUEST_URI"], '?'), '/');
    }

    public static function header($key)
    {
        $key = strtolower($key);

        foreach (getallheaders() as $name => $value) {
            if (strtolower($name) === $key) {
                return trim($value);
            }
        }
    }

    public static function setContext($key,$value)
    {
        self::$context[$key] = $value;
    }

    public static function context($key)
    {
        return self::$context[$key] ?? null;
    }
}