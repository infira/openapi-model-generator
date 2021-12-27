<?php

namespace Infira\omg\templates;


use Infira\omg\helper\Utils;

class SchemaObjectModel extends SchemaModel
{
	public function addPropertyConfig(string $name, string $phpType, ?string $dataClass, $schema, $property)
	{
		if ($dataClass) {
			$this->import($dataClass);
			$dataClass = Utils::extractName($dataClass);
		}
		$required = $schema->required && in_array($name, $schema->required);
		$this->addConstructorLine('$this->properties[\'%s\'] = [%s];', $name, $this->makePropertyConfig($phpType, $dataClass, $required, $property, $this->generator->getSchemaLocation($name)));
	}
}