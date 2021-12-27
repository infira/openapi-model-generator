<?php

namespace Infira\omg\templates;


use cebe\openapi\spec\Schema;
use Infira\omg\Omg;
use Infira\omg\Config;
use Infira\omg\helper\Utils;

class SchemaModel extends Objekt
{
	protected function makePropertyConfig(string $phpType, ?string $dataClass, bool $required, Schema $property, string $schemaLocation): string
	{
		$propertyConf       = [];
		$propertyConf['vt'] = Utils::toPhpType($phpType);
		
		if ($dataClass) {
			$this->import($dataClass);
			$dataClass = Utils::extractName($dataClass);
		}
		$propertyConf['dm'] = $dataClass ? "$dataClass::class" : 'null';
		
		
		$propertyConf['enum'] = 'null';
		if ($property->enum) {
			$propertyConf['enum'] = json_encode($property->enum);
		}
		
		$propertyConf['nl']  = $property->nullable;
		$propertyConf['req'] = $required;
		
		$ser     = (array)$property->getSerializableData();
		$unKnown = 'Storage::NOT_SET';
		
		
		if (array_key_exists('default', $ser)) {
			$defaultType = strtolower(gettype($property->default));
			if (in_array($defaultType, ['integer', 'double']) and $phpType == 'number') {
				$defaultType = 'number';
			}
			
			if ($defaultType != $phpType) {
				Omg::error("Property '$schemaLocation' default type('$defaultType') cannot be different with defined type('$phpType')");
			}
			$propertyConf['def'] = $property->default;
		}
		else {
			$this->import(Omg::getLibPath('Storage'), 'Storage');
			$propertyConf['def'] = $unKnown;
			if (Config::$nullableDefaultNull and $property->nullable) {
				$propertyConf['def'] = null;
			}
		}
		
		if (isset($property->maximum)) {
			$propertyConf['max'] = $property->maximum;
		}
		if (isset($property->minimum)) {
			$propertyConf['min'] = $property->minimum;
		}
		
		foreach ($propertyConf as $n => $v) {
			if ($n == 'enum' or $n == 'dm' or ($n == 'def' and $v == $unKnown) or is_integer($v) or (is_string($v) and strpos($v, 'Storage::') !== false)) {
				//just void
			}
			elseif (is_array($v)) {
				if ($v) {
					$v = json_encode($v);
				}
				else {
					$v = '[]';
				}
			}
			elseif (is_bool($v)) {
				$v = $v ? 'true' : 'false';
			}
			elseif ($v === null) {
				$v = 'null';
			}
			elseif (is_string($v)) {
				$v = $v ? "'$v'" : "''";
			}
			else {
				Omg::notImplementedYet();
				//$v = "'$v'";
			}
			$propertyConf[$n] = "'$n' => " . $v;
		}
		
		return join(', ', $propertyConf);
	}
}