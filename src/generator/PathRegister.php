<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Paths;
use Infira\omg\templates\libs\Register;

/**
 * @property-read Register $tpl
 */
class PathRegister extends \Infira\omg\Generator
{
	/**
	 * @var \cebe\openapi\spec\Paths
	 */
	private $paths;
	
	public function __construct(Paths $paths)
	{
		parent::__construct('/path/Register', 'register', Register::class);
		$this->paths = $paths;
	}
	
	public function make(): string
	{
		/** @var \cebe\openapi\spec\PathItem $def */
		foreach ($this->paths as $path => $def) {
			$operations = $def->getOperations();
			foreach ($operations as $method => $operation) {
				$operationGenerator = new PathOperation($path, $method, $operation);
				$operationGenerator->make();
				
				$this->tpl->addPath($path, $method, $operationGenerator->getFullClassPath());
			}
		}
		
		return parent::make();
	}
}