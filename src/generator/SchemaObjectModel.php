<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Reference;
use Infira\omg\Omg;

class SchemaObjectModel extends ObjectTemplate
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation);
		$this->setExtender($this->getLibClassPath('RObject'));
	}
	
	public function make(): string
	{
		$schema = $this->schema;
		if ($schema)
		{
			foreach ($schema->properties as $propertyName => $property)
			{
				$dataClass = null;
				
				if ($property instanceof Reference)
				{
					$ref = $property->getReference();
					/**
					 * @var Schema $resolved
					 */
					$resolved = $property->resolve();
					if (Omg::isMakeable($resolved->type) and Omg::isComponentRef($ref))
					{
						$dataClass = $this->getReferenceClassPath($ref);
					}
					$propertyPhpType = $resolved->type;
					$property        = $resolved;
				}
				elseif ($property->type == 'array')
				{
					if ($property->items instanceof Reference)
					{
						$ref = $property->items->getReference();
						if (Omg::isComponentRef($ref))
						{
							$sloc = "properties/$propertyName" . '/$ref:' . $ref;
						}
						else
						{
							Omg::notImplementedYet();
						}
					}
					else
					{
						$sloc = "properties/$propertyName";
					}
					$generator = $this->getPropertyModelGenerator('array', $property, $propertyName, $sloc);
					$dataClass = $generator->getFullClassPath();
					if (!Omg::isGenerated($generator->getNamespace()))
					{
						$generator->make();
					}
					$propertyPhpType = 'array';
				}
				elseif ($property->type == 'object')
				{
					//debug([$propertyName => $property]);
					$generator = $this->getPropertyModelGenerator('object', $property, $propertyName, "properties/$propertyName");
					$generator->make();
					$dataClass       = $generator->getFullClassPath();
					$propertyPhpType = 'object';
				}
				else
				{
					$propertyPhpType = $property->type;
				}
				
				$bodyLines = [sprintf('$this->set(\'%s\', $value);', $propertyName), 'return $this;'];
				$this->addMethod('set' . ucfirst($propertyName), $propertyPhpType, $dataClass, 'value', $property->nullable, 'self', $property->description, $bodyLines);
				
				$this->addPropertyConfig($propertyName, $propertyPhpType, $dataClass, $schema, $property);
				
				$docPhpType = Omg::isMakeable($propertyPhpType) ? 'singleClass' : $propertyPhpType;
				$this->addDocProperty($propertyName, $docPhpType, $dataClass, $property->nullable);
				
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
	 * @return \Infira\omg\generator\SchemaArrayModel|\Infira\omg\generator\SchemaBlankModel|\Infira\omg\generator\SchemaObjectModel
	 */
	private function getPropertyModelGenerator(string $type, $schema, string $propertyName, string $schemaLocation)
	{
		$propertyName = ucfirst($propertyName);
		if (strpos($this->getNamespace("./../property/%className%$propertyName"), 'property\property') !== false)
		{
			$namespace = "../../property/%className%$propertyName";
		}
		else
		{
			$namespace = "../property/%className%$propertyName";
		}
		
		return $this->getGenerator($schema, $namespace, $schemaLocation, $type);
	}
}