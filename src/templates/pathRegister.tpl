<?php
//########################################################################################
//##############################                            ##############################
//############################## THIS IS AUTOGENERATED FILE ##############################
//##############################                            ##############################
//########################################################################################
{$namespace}

%usages%

class {$className}{$extends}
{
    private static $paths = [
{foreach from = $paths key = path item = methods}
        '{$path}' =>  [
{foreach from = $methods item = req}
            '{$req.method}' => '{$req.class}',
{/foreach}
        ],
{/foreach}
    ];

    public static function getOperation(string $method, string $path): ?{$returnType}
    {
        $cn = self::getOperationClass($method, $path);
        if (!$cn) {
            return null;
        }

        return new $cn();
    }

    public static function getOperationClass(string $method, string $path): ?string
    {
        if (!self::operationExists($method, $path)) {
            return null;
        }

        return self::$paths[$path][strtolower($method)];
    }

    public static function operationExists(string $method, string $path): bool
    {
        return isset(self::$paths[$path][strtolower($method)]);
    }

    public static function getPaths(): array
    {
        return self::$paths;
    }

    public static function getClasses(): array
    {
        $classes = [];
        foreach (self::$paths as $methods) {
            $classes = array_merge($classes,array_values($methods));
        }

        return $classes;
    }

}