<?php

namespace Infira\omg\templates;


use cebe\openapi\spec\{Schema, Reference};
use Infira\omg\Omg;
use Infira\omg\Config;
use Infira\omg\helper\Utils;

class SchemaModel extends Class__Construct
{
	/**
	 * @param string           $phpType
	 * @param string|null      $dataClass
	 * @param Schema|Reference $item
	 * @return void
	 */
	public function setArrayItemType(string $phpType, ?string $dataClass, object $item)
	{
		$item = ($item instanceof Reference) ? $item->resolve() : $item;
		$this->addConstructorLine('$this->setItemConfig([%s]);', $this->makePropertyConfig($phpType, $dataClass, false, $item, $this->generator->schemaLocation->get()));
		
		$method = $this->createMethod('add', $item->description);
		$method->addBodyLine('$this->offsetSet(null, $item);', 'return $this;');
		
		$finalType = $dataClass ?: $phpType;
		
		if ($finalType and Utils::isClassLike($finalType)) {
			$this->import($finalType);
			$this->addDocMethodComment('createItemDataModel', 'string $key = null, $filler = Storage::NOT_SET', Utils::extractName($finalType));
		}
		$method->addTypeParameter('item', $finalType);
		$method->setReturnType('self', 'self');
	}
	
	public function addPropertyConfig(string $name, string $phpType, ?string $dataClass, $schema, $property)
	{
		$required = $schema->required && in_array($name, $schema->required);
		$this->addConstructorLine('$this->properties[\'%s\'] = [%s];', $name, $this->makePropertyConfig($phpType, $dataClass, $required, $property, $this->generator->schemaLocation->get($name)));
	}
	
	
	protected function makePropertyConfig(string $phpType, ?string $dataClass, bool $required, Schema $property, string $schemaLocation): string
	{
		$propertyConf       = [];
		$propertyConf['vt'] = Utils::toPhpType($phpType);
		
		if ($dataClass) {
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
			$this->importLib('Storage');
			$propertyConf['def'] = $unKnown;
			if (Config::$nullableDefaultNull and $property->nullable) {
				$propertyConf['def'] = null;
			}
		}
		$propertyConf['frm'] = $property->format ?? $unKnown;
		
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