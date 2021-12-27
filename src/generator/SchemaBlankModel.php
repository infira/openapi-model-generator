<?php

namespace Infira\omg\generator;

use Infira\omg\templates\Objekt;

class SchemaBlankModel extends ObjectTemplate
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, Objekt::class);
	}
}