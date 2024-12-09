<?php

namespace Infira\omg\helper;

use Infira\omg\Omg;
use Wolo\File\File;
use Wolo\File\Path;
use Wolo\Str;

class Tpl
{
    public static function load(string $name, array $variables = [], array $replaces = []): string
    {
        $tpl = Path::join(__DIR__, "../templates/$name");
        if (!file_exists($tpl)) {
            Omg::error("template $tpl does not exist");
        }
        $src = File::content($tpl);
        $src = preg_replace('/\/\/(%\w+\%)/m', '$1', $src);

        $res = Str::vars($src, $variables, '%%');
        foreach ($replaces as $from => $to) {
            $res = str_replace($from, $to, $res);
        }

        return $res;
    }
}