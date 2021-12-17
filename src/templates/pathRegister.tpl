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
{foreach from = $paths item = req}
    '[{$req.method}]{$req.path}' => '{$req.class}',
{/foreach}
];

    public static function getOperation(string $method, string $path): ?{$returnType}
    {
        $key = sprintf('[%s]%s', strtolower($method), $path);
        if (!isset(self::$paths[$key]))
        {
            return null;
        }
        $cn = self::$paths[$key];

        return new $cn();
    }
}