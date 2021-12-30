<?php

namespace Infira\omg\helper;

use Nette\PhpGenerator\Literal;
use Infira\omg\Config;

class Utils extends \Infira\console\helper\Utils
{
	public static function parseValueFormat($value, ?string $valueFormat = "'%s'"): array
	{
		$valueFormat = $valueFormat ?: "'%s'";
		
		if (is_bool($value)) {
			$valueFormat = '%s';
			$value       = $value ? 'true' : 'false';
		}
		elseif ($value === null) {
			$valueFormat = '%s';
			$value       = 'null';
		}
		elseif (is_array($value)) {
			$valueFormat = "[%s]";
			$value       = join(',', $value);
		}
		elseif (is_string($value) and strpos($value, 'Poesis::NONE') !== false or is_integer($value) or is_float($value)) {
			$valueFormat = '%s';
		}
		elseif (is_object($value) and $value instanceof Literal) {
			$valueFormat = '%s';
			$value       = $value->__toString();
		}
		
		return [$valueFormat, $value];
	}
	
	public static function literal(string $value): Literal
	{
		return new Literal($value);
	}
	
	public static function ns(string ...$parts): Ns
	{
		$ns = new Ns(Config::getRootNamespace(), '\\');
		if ($parts) {
			$parts[0] = str_replace('#/', '/', $parts[0]);
			$ns->set(...$parts);
		}
		
		return $ns;
	}
	
	public static function printNette(object $file): string
	{
		$printer = new NettePrinter();
		
		return $printer->printFile($file);
	}
}