<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Reference;
use Infira\omg\Omg;

class SchemaArrayModel extends ObjectTemplate
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation);
		$this->setExtender($this->getLibClassPath('RArray'));
	}
	
	public function make(): string
	{
		$schema = $this->schema;
		if ($schema)
		{
			if ($schema->items instanceof Reference)
			{
				$resolved = $schema->items->resolve();
				$ref      = $schema->items->getReference();
				if (Omg::isComponentRef($ref) and Omg::isMakeable($resolved->type))
				{
					$this->setArrayItemType($resolved->type, $this->getReferenceClassPath($ref), $resolved);
				}
				else
				{
					$this->setArrayItemType($resolved->type, null, $resolved);
				}
			}
			elseif ($schema->items->allOf)
			{
				$generator = $this->getGenerator($schema->items, '../arrayItem/%className%', 'items', 'object');
				$generator->make();
				$this->setArrayItemType('array', $generator->getFullClassPath(), $schema->items);
			}
			elseif ($schema->items->properties)
			{
				$generator = $this->getGenerator($schema->items, '../arrayItem/%className%', '', 'object');
				$generator->make();
				$this->setArrayItemType('object', $generator->getFullClassPath(), $schema->items);
			}
			else
			{
				if (!$schema->items->type)
				{
					Omg::error('type not defined');
				}
				if (Omg::isMakeable($schema->items->type))
				{
					$generator = $this->getGenerator($schema->items, '../arrayItem/%className%', 'items', $schema->items->type);
					$generator->make();
					$this->setArrayItemType($schema->items->type, $generator->getFullClassPath(), $schema->items);
				}
				else
				{
					$this->setArrayItemType($schema->items->type, null, $schema->items);
				}
			}
		}
		
		return parent::make();
	}
	
	private function setArrayItemType(string $phpType, ?string $dataClass, Schema $item)
	{
		$this->addConstructorLine('$this->setItemConfig([%s]);', $this->makePropertyConfig($phpType, $dataClass, false, $item, $this->getSchemaLocation()));
		$this->setExtender($this->getLibClassPath('RArray'));
		$this->addMethod('add', $phpType, $dataClass, 'item', $item->nullable, 'self', $item->description, ['$this->offsetSet(null, $item);', 'return $this;']);
	}
}