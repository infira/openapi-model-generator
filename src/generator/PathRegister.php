<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Paths;

class PathRegister extends \Infira\omg\Generator
{
	public function __construct()
	{
		parent::__construct('/path/Register', 'register');
		$this->setTemplate('pathRegister.tpl');
	}
	
	public function make(Paths $paths)
	{
		$this->setVariable('returnType', $this->getLibClassPath('Operation'));
		/** @var \cebe\openapi\spec\PathItem $def */
		foreach ($paths as $path => $def)
		{
			$operations = $def->getOperations();
			foreach ($operations as $method => $operation)
			{
				$operationGenerator = new PathOperation($path, $method, $operation);
				$operationGenerator->make();
				
				$this->add2Variable('paths', ['path' => $path, 'method' => $method, 'class' => $operationGenerator->getFullClassPath()]);
			}
		}
		$this->makeClass();
	}
}