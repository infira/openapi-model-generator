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
		$ex = explode('\\', $namespace);
		
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
	
	public static function makeParameterType(?string $argType, ?string $dataClass, bool $nullable): string
	{
		if ($argType == 'singleClass') {
			$argType = $dataClass;
		}
		else {
			if ($dataClass) {
				return '';
			}
			else {
				$argType = self::toPhpType($argType);
				if ($nullable and $argType) {
					$argType = "?$argType";
				}
			}
		}
		
		return $argType;
	}
	
	public static function makeDocType(string $phpType, ?string $dataClass, bool $nullable): string
	{
		$docValueType = [];
		if ($nullable) {
			$docValueType[] = 'null';
		}
		if ($phpType == 'singleClass') {
			$docValueType[] = $dataClass;
		}
		elseif ($dataClass) {
			$docValueType[] = 'array';
			$docValueType[] = '\stdClass';
			$docValueType[] = $dataClass;
		}
		else {
			$docValueType = [self::toPhpType($phpType)];
		}
		
		return join('|', $docValueType);
	}
}