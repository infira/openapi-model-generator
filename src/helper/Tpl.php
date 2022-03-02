<?php

namespace Infira\omg\helper;

use Wolo\File\File;
use Wolo\Str;
use Infira\omg\Omg;
use Infira\console\Bin;

class Tpl
{
	public static function load(string $name, array $variables = [], array $replaces = []): string
	{
		$tpl = Bin::getPath("../src/templates/$name");
		if (!file_exists($tpl)) {
			Omg::error("template $tpl does not exist");
		}
		$src = File::content($tpl);
		$src = preg_replace('/\/\/(%\w+\%)/m', '$1', $src);
		
		$res = Str::vars($src, $variables);
		foreach ($replaces as $from => $to) {
			$res = str_replace($from, $to, $res);
		}
		
		return $res;
	}
}