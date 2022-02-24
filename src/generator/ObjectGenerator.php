<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use cebe\openapi\spec\Schema;
use Infira\omg\Omg;
use Infira\omg\helper\Utils;
use Infira\omg\templates\SchemaModel;
use Infira\omg\helper\ParametersSpec;

/**
 * @property-read SchemaModel $tpl
 */
abstract class ObjectGenerator extends Generator
{
	public $schema = null;
	
	private $extendableLib = null;
	
	public function __construct(string $namespace, string $schemaLocation, string $lib = null)
	{
		parent::__construct($namespace, $schemaLocation, SchemaModel::class);
		$this->extendableLib = $lib;
	}
	
	/**
	 * @param Schema|null $schema
	 * @return void
	 */
	public function setSchema($schema)
	{
		Omg::validateSchema($schema);
		$this->schema = $schema;
	}
	
	/**
	 * @return \Infira\omg\generator\ObjectGenerator
	 */
	public function make()
	{
		if (!$this->tpl->getExtends() and $this->extendableLib) {
			$this->tpl->extendLib($this->extendableLib);
		}
		if ($this->schema and (!$this->schema instanceof ParametersSpec)) {
			if ($this->schema->nullable) {
				$this->tpl->addConstructorLine('$this->nullable = true;');
			}
			if ($this->schema->description) {
				$this->tpl->addComment('Schema description: %s', $this->schema->description);
			}
		}
		$this->tpl->import(Omg::getLibPath('Storage'), 'Storage');
		$this->tpl->constructor->addParameter('fill')->setDefaultValue(Utils::literal('Storage::NOT_SET'));
		$this->tpl->addConstructorLine('parent::__construct($fill);');
		
		return parent::make();
	}
}