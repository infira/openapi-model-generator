<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use Infira\console\helper\Utils;
use Infira\omg\Omg;
use Infira\omg\helper\ParametersSpec;

/**
 * @property ParametersSpec $schema
 */
class SchemaRequestParameterObjectGenerator extends ObjectGenerator
{
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, 'RObject');
	}
	
	public function setSchema($schema)
	{
		$this->schema = $schema;
	}
	
	/**
	 * @return SchemaRequestParameterObjectGenerator
	 */
	public function make()
	{
		foreach ($this->schema->getParameters() as $parameter) {
			$parameter         = $parameter instanceof Reference ? $parameter->resolve() : $parameter;
			$parameter->schema = $parameter->schema instanceof Reference ? $parameter->schema->resolve() : $parameter->schema;
			
			$ucParamname    = ucfirst($parameter->name);
			$namespace      = "../parameters/%className%$ucParamname" . "Parameter";
			$schemaLocation = "properties/$parameter->name";
			
			$phpTyoe = $parameter->schema->type;
			
			$method = $this->tpl->createMethod('set' . $ucParamname, $parameter->description);
			$method->addTypeParameter('value', $phpTyoe);
			$method->addBodyLine(sprintf('$this->set(\'%s\', $value)', $parameter->name), 'return $this');
			$method->setReturnType('self', 'self');
			
			$this->tpl->addPropertyConfig($parameter->name, $phpTyoe, null, $parameter->schema, $parameter->schema);
			
			$this->tpl->addDocPropertyComment($parameter->name, $phpTyoe, $parameter->description);
		}
		
		$pathProp = $this->tpl->addProperty('path', '');
		$pathProp->addComment('@var string Operation request path');
		$pathProp->setPrivate();
		$this->tpl->addConstructorLine('$this->path = $path;');
		$pathParam = $this->tpl->constructor->addParameter('path')->setDefaultValue('');
		$pathParam->setType('string');
		
		/*
		$fillFromRequestURI = $this->tpl->createMethod('fillFromRequestURI', 'Fille from request URI ex /page/10?somePara=1');
		$fillFromRequestURI->addTypeParameter('uri', 'string');
		$fillFromRequestURI->addBodyLine(sprintf('$this->set(\'%s\', $uri)', $parameter->name), 'return $this');
		$fillFromRequestURI->setReturnType('self', 'self');
		*/
		
		return parent::make();
	}
}