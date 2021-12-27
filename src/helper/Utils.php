<?php

namespace Infira\omg\helper;

use Nette\PhpGenerator\Literal;

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
		elseif (is_string($value) and substr($value, 0, 6) == 'CLEAN=') {
			$valueFormat = '%s';
			$value       = substr($value, 6);
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
	
	public static function extractName(string $namespace): string
	{
		$ex = explode('\\', str_replace('/', '\\', $namespace));
		
		return end($ex);
	}
	
	public static function toPhpType(string $type): string
	{
		$convertTypes = ['integer' => 'int', 'number' => 'float', 'boolean' => 'bool'];
		if (isset($convertTypes[$type])) {
			return $convertTypes[$type];
		}
		
		return $type;
	}
	
	public static function makePhpTypes(string $typeStr, bool $extractClassName): array
	{
		$typeStr = trim($typeStr);
		$types   = [];
		if ($typeStr[0] == "?") {
			$types[] = 'null';
			$typeStr = substr($typeStr, 1);
		}
		
		if ($typeStr[0] == '\\') {
			$types[] = 'array';
			$types[] = '\stdClass';
			$types[] = $extractClassName ? self::extractName($typeStr) : $typeStr;
		}
		else {
			$types[] = self::toPhpType($typeStr);
		}
		
		return $types;
	}
	
	public static function isClassLike(string $str): bool
	{
		return (bool)preg_match('/\w+\\\\/m', $str);
	}
	
	public static function extractClass(string $str): string
	{
		if ($str[0] == "?") {
			$str = substr($str, 1);
		}
		
		return sprintf('%s::class', self::extractName($str));
	}
}