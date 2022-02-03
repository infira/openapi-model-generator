<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Reference;
use Infira\omg\Omg;
use Infira\console\helper\Utils;

class SchemaObjectGenerator extends ObjectGenerator
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, 'RObject');
	}
	
	public function make(): string
	{
		$schema = $this->schema;
		if ($schema) {
			foreach ($schema->properties as $propertyName => $property) {
				$dataClass = null;
				
				if ($property instanceof Reference) {
					$ref = $property->getReference();
					/**
					 * @var Schema $resolved
					 */
					$resolved = $property->resolve();
					if (Omg::isMakeable($resolved->type) and Omg::isComponentRef($ref)) {
						$dataClass = Omg::getReferenceClassPath($ref);
					}
					$propertyPhpType = $resolved->type;
					$property        = $resolved;
				}
				elseif ($property->type == 'array' && $property->items instanceof Reference) {
					$ref = $property->items->getReference();
					if (Omg::isComponentRef($ref)) {
						$sloc      = "properties/$propertyName" . '/$ref:' . $ref;
						$generator = $this->getPropertyModelGenerator('array', $property, $propertyName, $sloc);
						$dataClass = $generator->getFullClassPath();
						if (!Omg::isGenerated($generator->ns->get())) {
							$generator->make();
						}
					}
					else {
						Omg::notImplementedYet();
					}
					
					$propertyPhpType = 'array';
				}
				elseif ($property->type == 'array') {
					$sloc      = "properties/$propertyName";
					$generator = $this->getPropertyModelGenerator('array', $property, $propertyName, $sloc);
					$dataClass = $generator->getFullClassPath();
					if (!Omg::isGenerated($generator->ns->get())) {
						$generator->make();
					}
					$propertyPhpType = 'array';
				}
				elseif ($property->type == 'object') {
					//debug([$propertyName => $property]);
					$generator = $this->getPropertyModelGenerator('object', $property, $propertyName, "properties/$propertyName");
					$generator->make();
					$dataClass       = $generator->getFullClassPath();
					$propertyPhpType = 'object';
				}
				else {
					$propertyPhpType = $property->type;
				}
				
				$finalType = $propertyPhpType;
				if ($dataClass) {
					$finalType = $dataClass;
				}
				if ($finalType and Utils::isClassLike($finalType)) {
					$this->tpl->import($finalType);
				}
				
				$method = $this->tpl->createMethod('set' . ucfirst($propertyName), $property->description);
				$method->addTypeParameter('value', $finalType);
				$method->addBodyLine(sprintf('$this->set(\'%s\', $value)', $propertyName), 'return $this');
				$method->setReturnType('self', 'self');
				
				$this->tpl->addPropertyConfig($propertyName, $propertyPhpType, $dataClass, $schema, $property);
				
				$finalType = $property->nullable === true ? "?$finalType" : $finalType;
				$this->tpl->addDocPropertyComment($propertyName, $finalType, $property->description);
				
				unset($propertyPhpType);
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