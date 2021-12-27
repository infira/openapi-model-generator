<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use cebe\openapi\spec\Schema;
use Infira\omg\Omg;
use Infira\omg\templates\Objekt;
use Nette\PhpGenerator\Literal;

/**
 * @property-read Objekt $tpl
 */
abstract class ObjectTemplate extends Generator
{
	public $schema = null;
	
	public function setSchema(?Schema $schema)
	{
		$this->schema = $schema;
	}
	
	public function make(): string
	{
		if ($this->schema) {
			if ($this->schema->nullable) {
				$this->tpl->addConstructorLine('$this->nullable = true;');
			}
			if ($this->schema->description) {
				$this->tpl->addComment('Schema description: %s', $this->schema->description);
			}
		}
		$this->tpl->import(Omg::getLibPath('Storage'), 'Storage');
		$this->tpl->constructor->addParameter('fill')->setDefaultValue(new Literal('Storage::NOT_SET'));
		$this->tpl->addConstructorLine('parent::__construct($fill);');
		
		return $this->makeClass();
	}
}