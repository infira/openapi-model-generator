<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Paths;
use Infira\omg\templates\Register;

/**
 * @property-read Register $tpl
 */
class PathRegister extends \Infira\omg\Generator
{
	public function __construct()
	{
		parent::__construct('/path/Register', 'register', Register::class);
	}
	
	public function make(Paths $paths)
	{
		/** @var \cebe\openapi\spec\PathItem $def */
		foreach ($paths as $path => $def) {
			$operations = $def->getOperations();
			foreach ($operations as $method => $operation) {
				$operationGenerator = new PathOperation($path, $method, $operation);
				$operationGenerator->make();
				
				$this->tpl->addPath($path, $method, $operationGenerator->getFullClassPath());
			}
		}
		$this->makeClass();
	}
}