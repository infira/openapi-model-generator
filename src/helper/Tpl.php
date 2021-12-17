<?php
namespace Infira\omg\helper;

use Infira\Utils\RuntimeMemory as Rm;
use Infira\Utils\File;
use Infira\Utils\Variable;
use Infira\omg\Omg;
use Infira\console\Bin;

class Tpl
{
	const REMOVE_LINE = '__REMOVE_LINE__';
	
	public static function __callStatic($method, $args)
	{
		return self::Smarty()->$method(...$args);
	}
	
	public static function render(string $tpl, array $vars = [])
	{
		$smarty = self::Smarty();
		$smarty->assign($vars);
		if (!file_exists($tpl))
		{
			alert("Smarty template <strong>" . $tpl . "</strong> ei leitud");
			
			return false;
		}
		
		return $smarty->fetch($tpl);
	}
	
	public static function assign($var, $value = null): \Smarty
	{
		return self::Smarty()->assign($var, $value);
	}
	
	public static function Smarty(): \Smarty
	{
		return Rm::once('View->Smarty', function ()
		{
			$smarty                   = new \Smarty();
			$smarty->error_unassigned = true;
			$smarty->error_reporting  = E_ALL;
			$smarty->caching          = false;
			$smarty->compile_check    = true;
			$smarty->force_compile    = true;
			$smarty->setCompileDir(Bin::getPath('../tmp/compiledTemplates'));
			
			return $smarty;
		});
	}
	
	public static function load(string $name, array $variables = [], array $replaces = []): string
	{
		$ext = File::getExtension($name);
		$tpl = Bin::getPath("../src/templates/$name");
		if (!file_exists($tpl))
		{
			Omg::error("template $tpl does not exist");
		}
		if (in_array($ext, ['txt', 'php']))
		{
			$src = File::getContent($tpl);
			$src = preg_replace('/\/\/(%\w+\%)/m', '$1', $src);
			
			$res = Variable::assign($variables, $src);
		}
		else
		{
			self::assign($variables);
			$res = self::render($tpl);
		}
		foreach ($replaces as $from => $to)
		{
			$res = str_replace($from, $to, $res);
		}
		
		return $res;
	}
}