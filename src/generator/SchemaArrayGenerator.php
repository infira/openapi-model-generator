<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Reference;
use Infira\omg\Omg;
use cebe\openapi\spec\Schema;

class SchemaArrayGenerator extends ObjectGenerator
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, 'RArray');
	}
	
	/**
	 * @throws \Exception
	 * @return SchemaArrayGenerator
	 */
	public function make()
	{
		$schema = $this->schema;
		if ($schema) {
			if (!($schema->items instanceof Reference) and is_array($schema->items->type)) {
				$this->tpl->setArrayItemType('mixed', null, $schema->items);
			}
			elseif ($schema->items instanceof Reference or ($schema instanceof Schema and ($schema->items->allOf or $schema->items->properties)) or Omg::isMakeable($schema->items->type)) {
				$make = $this->makeIfNeeded($schema->items, '../arrayItem/%className%Item', 'items/item');
				$this->tpl->setArrayItemType($make->type, $make->dataClass, $schema->items);
			}
			elseif ($schema->items->type) {
				$this->tpl->setArrayItemType($schema->items->type, null, $schema->items);
			}
			else {
				
				Omg::error('type not defined', ['type' => $schema->items->type]);
			}
		}
		
		return parent::make();
	}
}