<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Reference;
use Infira\omg\Omg;

class SchemaArrayGenerator extends ObjectGenerator
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, 'RArray');
	}
	
	public function make(): string
	{
		$schema = $this->schema;
		if ($schema) {
			if ($schema->items instanceof Reference) {
				$resolved = $schema->items->resolve();
				$ref      = $schema->items->getReference();
				if (Omg::isComponentRef($ref) and Omg::isMakeable($resolved->type)) {
					$this->tpl->setArrayItemType($resolved->type, Omg::getReferenceClassPath($ref), $resolved);
				}
				else {
					$this->tpl->setArrayItemType($resolved->type, null, $resolved);
				}
			}
			elseif ($schema->items->allOf) {
				$generator = $this->getGenerator($schema->items, '../arrayItem/%className%', 'items', 'object');
				$generator->make();
				$this->tpl->setArrayItemType('array', $generator->getFullClassPath(), $schema->items);
			}
			elseif ($schema->items->properties) {
				$generator = $this->getGenerator($schema->items, '../arrayItem/%className%', '', 'object');
				$generator->make();
				$this->tpl->setArrayItemType('object', $generator->getFullClassPath(), $schema->items);
			}
			else {
				if (!$schema->items->type) {
					Omg::error('type not defined');
				}
				if (Omg::isMakeable($schema->items->type)) {
					$generator = $this->getGenerator($schema->items, '../arrayItem/%className%', 'items', $schema->items->type);
					$generator->make();
					$this->tpl->setArrayItemType($schema->items->type, $generator->getFullClassPath(), $schema->items);
				}
				else {
					$this->tpl->setArrayItemType($schema->items->type, null, $schema->items);
				}
			}
		}
		
		return parent::make();
	}
}