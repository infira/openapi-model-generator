<?php

namespace Infira\omg\helper;

use Infira\Utils\File;
use Infira\Utils\Variable;
use Infira\omg\Omg;
use Infira\console\Bin;

class Tpl
{
	const REMOVE_LINE = '__REMOVE_LINE__';
	
	public static function load(string $name, array $variables = [], array $replaces = []): string
	{
		$tpl = Bin::getPath("../src/templates/$name");
		if (!file_exists($tpl)) {
			Omg::error("template $tpl does not exist");
		}
		$src = File::getContent($tpl);
		$src = preg_replace('/\/\/(%\w+\%)/m', '$1', $src);
		
		$res = Variable::assign($variables, $src);
		foreach ($replaces as $from => $to) {
			$res = str_replace($from, $to, $res);
		}
		
		return $res;
	}
}