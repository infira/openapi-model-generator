<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use Infira\omg\templates\ClassTemplate;

class SchemaBlankGenerator extends Generator
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, ClassTemplate::class);
	}
}