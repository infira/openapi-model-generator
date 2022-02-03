<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Reference;
use Infira\console\helper\Utils;

class SchemaObjectGenerator extends ObjectGenerator
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, 'RObject');
	}
	
	/**
	 * @return SchemaObjectGenerator
	 */
	public function make()
	{
		$schema = $this->schema;
		if ($schema) {
			foreach ($schema->properties as $propertyName => $property) {
				$dataClass = null;
				
				$ucPropertyName = ucfirst($propertyName);
				if (strpos($this->ns->get("./../property/%className%$ucPropertyName"), 'property\property') !== false) {
					$namespace = "../../property/%className%$ucPropertyName";
				}
				else {
					$namespace = "../property/%className%$ucPropertyName";
				}
				$schemaLocation  = "properties/$propertyName";
				$make            = $this->makeIfNeeded($property, $namespace, $schemaLocation);
				$dataClass       = $make->dataClass;
				$propertyPhpType = $make->type ?: $property->type;
				$finalType       = $propertyPhpType;
				
				if ($dataClass) {
					$finalType = $dataClass;
				}
				if ($finalType and Utils::isClassLike($finalType)) {
					$this->tpl->import($finalType);
				}
				$property = ($property instanceof Reference) ? $property->resolve() : $property;
				$method   = $this->tpl->createMethod('set' . ucfirst($propertyName), $property->description);
				$method->addTypeParameter('value', $finalType);
				$method->addBodyLine(sprintf('$this->set(\'%s\', $value)', $propertyName), 'return $this');
				$method->setReturnType('self', 'self');
				
				$this->tpl->addPropertyConfig($propertyName, $propertyPhpType, $dataClass, $schema, $property);
				
				$finalType = $property->nullable === true ? "?$finalType" : $finalType;
				$this->tpl->addDocPropertyComment($propertyName, $finalType, $property->description);
			}
		}
		
		return parent::make();
	}
	
	
	/**
	 * @param string|null      $type
	 * @param Reference|Schema $schema
	 * @param string           $propertyName
	 * @param string           $schemaLocation
	 *
	 * @return \Infira\omg\generator\SchemaArrayGenerator|\Infira\omg\generator\SchemaBlankGenerator|\Infira\omg\generator\SchemaObjectGenerator
	 */
	private function getPropertyModelGenerator(string $type, $schema, string $propertyName, string $schemaLocation)
	{
		$propertyName = ucfirst($propertyName);
		if (strpos($this->ns->get("./../property/%className%$propertyName"), 'property\property') !== false) {
			$namespace = "../../property/%className%$propertyName";
		}
		else {
			$namespace = "../property/%className%$propertyName";
		}
		
		return $this->getGenerator($schema, $namespace, $schemaLocation, $type);
	}
}