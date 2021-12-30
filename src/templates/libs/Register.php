<?php

namespace Infira\omg\templates\libs;


use Nette\PhpGenerator\ClassType;
use Infira\omg\Omg;
use Infira\omg\templates\ClassTemplate;
use Infira\omg\Config;

class Register extends ClassTemplate
{
	/**
	 * @var \Nette\PhpGenerator\Property
	 */
	private $pathsProp;
	
	private $paths = [];
	
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->pathsProp = $this->addProperty('paths');
		$this->pathsProp->setStatic(true)->setPrivate();
	}
	
	public function addPath(string $path, string $method, string $class)
	{
		$this->paths[$path][$method] = $class;
	}
	
	public function beforeFinalize()
	{
		$this->pathsProp->setValue($this->paths);
		if (Config::$phpVersion > 7.3) {
			$this->pathsProp->setType('array');
		}
		
		$getOperation = $this->createMethod('getOperation');
		$getOperation->setReturnType(Omg::getOperationPath())->setReturnNullable(true);
		$getOperation->setStatic(true);
		$getOperation->addParameter('method')->setType('string');
		$getOperation->addParameter('path')->setType('string');
		$getOperation->setBody('$cn = self::getOperationClass($method, $path);
if (!$cn) {
    return null;
}

return new $cn();');
		
		
		$getOperationClass = $this->createMethod('getOperationClass');
		$getOperationClass->setReturnType('string')->setReturnNullable(true);
		$getOperationClass->setStatic(true);
		$getOperationClass->addParameter('method')->setType('string');
		$getOperationClass->addParameter('path')->setType('string');
		$getOperationClass->setBody('if (!self::operationExists($method, $path)) {
    return null;
}

return self::$paths[$path][strtolower($method)];');
		
		$operationExists = $this->createMethod('operationExists');
		$operationExists->setReturnType('bool');
		$operationExists->setStatic(true);
		$operationExists->addParameter('method')->setType('string');
		$operationExists->addParameter('path')->setType('string');
		$operationExists->setBody('return isset(self::$paths[$path][strtolower($method)]);');
		
		$getPaths = $this->createMethod('getPaths');
		$getPaths->setReturnType('array');
		$getPaths->setStatic(true);
		$getPaths->setBody('return self::$paths;');
		
		$getClasses = $this->createMethod('getClasses');
		$getClasses->setReturnType('array');
		$getClasses->setStatic(true);
		$getClasses->setBody('$classes = [];
foreach (self::$paths as $path => $methods) {
	foreach ($methods as $methodClass) {
        $classes[] = $methodClass;
	}
}

return $classes;');
	}
}