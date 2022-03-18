<?php

namespace Infira\omg\templates;


use Nette\PhpGenerator\ClassType;

class Class__Construct extends ClassTemplate
{
	/**
	 * @var \Nette\PhpGenerator\Method
	 */
	public $constructor;
	
	
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->constructor = $this->createMethod('__construct');
	}
	
	public function addConstructorLine(string $format, string ...$values)
	{
		$this->constructor->addBodyLine(vsprintf($format, $values));
	}
}