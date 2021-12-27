<?php

namespace Infira\omg\templates;


use cebe\openapi\spec\Schema;
use Infira\omg\helper\Utils;

class SchemaArrayModel extends SchemaModel
{
	public function setArrayItemType(string $phpType, ?string $dataClass, Schema $item)
	{
		$this->addConstructorLine('$this->setItemConfig([%s]);', $this->makePropertyConfig($phpType, $dataClass, false, $item, $this->generator->getSchemaLocation()));
		$this->addMethod('add', $phpType, $dataClass, 'item', $item->nullable, 'self', $item->description, ['$this->offsetSet(null, $item);', 'return $this;']);
	}
}