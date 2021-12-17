<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use Infira\omg\helper\Tpl;
use cebe\openapi\spec\Schema;
use Infira\omg\Omg;
use Infira\omg\Config;

abstract class ObjectTemplate extends Generator
{
	public $schema = null;
	
	public function __construct(string $namespace = '/', string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation);
		$this->setTemplate('object.tpl');
		$this->setConstructorArguments('');
	}
	
	public function setSchema(?Schema $schema)
	{
		$this->schema = $schema;
	}
	
	public function make(): string
	{
		/*
		if ($this->getClassName() == 'orders200')
		{
			debug($this->getVariable('extender'));
		}
		*/
		//$bodyLines = [sprintf('parent::add(\'%s\', $item);', $propertyName), 'return $this;'];
		//$this->addMethod('addadasd', $phpType, $dataClass, 'item', $property->nullable, ucfirst($this->getClassName()), $property->description, $bodyLines);
		
		if ($this->schema)
		{
			if ($this->schema->nullable)
			{
				$this->addConstructorLine('$this->nullable = true;');
			}
			if ($this->schema->description)
			{
				$this->addDocDescriptionLine('Schema description: %s', $this->schema->description);
			}
		}
		$this->setConstructorArguments('$fill = ' . $this->getLibClassPath('Storage::NOT_SET'));
		$this->addConstructorLine('parent::__construct($fill);');
		
		return $this->makeClass();
	}
	
	protected final function addDocProperty(string $name, string $phpType, ?string $dataClass, bool $nullable, ?string $description = '')
	{
		$this->addDocItem('property', $name, $phpType, $dataClass, $nullable, $description);
	}
	
	protected final function addDocMethod(string $name, ?string $argName, ?string $phpType, ?string $dataClass, bool $nullable, ?string $description = '')
	{
		$item['argName'] = $argName ? '$' . $argName : '';
		$item['argType'] = $argName ? $this->makePhpArgumentType($phpType, $dataClass, $nullable) . ' ' : '';
		$this->addDocItem('method', $name, $phpType, $dataClass, $nullable, $description, $item);
	}
	
	private function addDocItem(string $type, string $name, string $phpType, ?string $dataClass, bool $nullable, ?string $description, array $item = [])
	{
		$item['docType'] = $this->makePhpDocType($phpType, $dataClass, $nullable);
		$item['name']    = $name;
		$item['desc']    = html_entity_decode($description);
		$item['type']    = $type;
		$this->add2Variable('docProperties', $item);
	}
	
	protected function addMethod(string $name, ?string $phpType, ?string $dataClass, string $argName, bool $nullable, string $returnType, ?string $description, array $bodyLines)
	{
		$argType     = '';
		$docArgument = Tpl::REMOVE_LINE;
		if ($argName)
		{
			$argType = $this->makePhpArgumentType($phpType, $dataClass, $nullable);
			$argType = $argType ? "$argType " : $argType;
			
			$docArgumentType = $this->makePhpDocType($phpType, $dataClass, $nullable);
			$docArgument     = sprintf('@param %s $%s', $docArgumentType, $argName);
		}
		$argName  = $argName ? '$' . $argName : '';
		$argument = sprintf('%s%s', $argType, $argName);
		
		
		$description = empty($description) ? Tpl::REMOVE_LINE : $description . "\n\t *";
		$this->add2Variable('methods', ['name' => $name, 'argument' => $argument, 'docArgument' => $docArgument, 'returnType' => $returnType, 'desc' => $description, 'bodyLines' => $bodyLines]);
	}
	
	public function addConstructorLine(string $format, string ...$values)
	{
		$this->add2Variable('constructorLines', vsprintf($format, $values));
	}
	
	public function addVariableLine(string $format, string ...$values)
	{
		$this->add2Variable('variableLines', vsprintf($format, $values));
	}
	
	protected final function setConstructorArguments(string $args)
	{
		$this->setVariable('constructorArguments', $args);
	}
	
	protected function addDocDescriptionLine(string $format, string ...$values)
	{
		$this->add2Variable('docDescriptionLines', ' * ' . vsprintf($format, $values));
	}
	
	protected function addPropertyConfig(string $name, string $phpType, ?string $dataClass, $schema, $property)
	{
		$required = $schema->required && in_array($name, $schema->required);
		$this->addConstructorLine('$this->properties[\'%s\'] = [%s];', $name, $this->makePropertyConfig($phpType, $dataClass, $required, $property, $this->getSchemaLocation($name)));
	}
	
	protected function makePropertyConfig(string $phpType, ?string $dataClass, bool $required, Schema $property, string $schemaLocation): string
	{
		$propertyConf         = [];
		$propertyConf['vt']   = $this->convertToPhpType($phpType);
		$propertyConf['dm']   = $dataClass;
		$propertyConf['enum'] = 'null';
		if ($property->enum)
		{
			$propertyConf['enum'] = json_encode($property->enum);
		}
		
		$propertyConf['nl']  = $property->nullable;
		$propertyConf['req'] = $required;
		
		$ser     = (array)$property->getSerializableData();
		$unKnown = $this->getLibClassPath('Storage::NOT_SET');
		
		
		if (array_key_exists('default', $ser))
		{
			$defaultType = strtolower(gettype($property->default));
			if (in_array($defaultType, ['integer', 'double']) and $phpType == 'number')
			{
				$defaultType = 'number';
			}
			
			if ($defaultType != $phpType)
			{
				Omg::error("Property '$schemaLocation' default type('$defaultType') cannot be different with defined type('$phpType')");
			}
			$propertyConf['def'] = $property->default;
		}
		else
		{
			$propertyConf['def'] = $unKnown;
			if (Config::$nullableDefaultNull and $property->nullable)
			{
				$propertyConf['def'] = null;
			}
		}
		
		if (isset($property->maximum))
		{
			$propertyConf['max'] = $property->maximum;
		}
		if (isset($property->minimum))
		{
			$propertyConf['min'] = $property->minimum;
		}
		
		foreach ($propertyConf as $n => $v)
		{
			if ($n == 'enum' or ($n == 'def' and $v == $unKnown) or is_integer($v) or (is_string($v) and strpos($v, 'Storage::') !== false))
			{
				//just void
			}
			elseif (is_array($v))
			{
				if ($v)
				{
					$v = json_encode($v);
				}
				else
				{
					$v = '[]';
				}
			}
			elseif (is_bool($v))
			{
				$v = $v ? 'true' : 'false';
			}
			elseif ($v === null)
			{
				$v = 'null';
			}
			elseif (is_string($v))
			{
				$v = $v ? "'$v'" : "''";
			}
			else
			{
				Omg::notImplementedYet();
				//$v = "'$v'";
			}
			$propertyConf[$n] = "'$n' => " . $v;
		}
		
		return join(', ', $propertyConf);
	}
}