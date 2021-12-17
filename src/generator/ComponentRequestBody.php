<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use Infira\omg\Config;
use cebe\openapi\spec\RequestBody;

class ComponentRequestBody extends Generator
{
	public function __construct(string $name)
	{
		parent::__construct("/component/requestBodies/$name", "/components/requestBodies/");
	}
	
	public function make(RequestBody $requestBody)
	{
		$generator              = $this->getGenerator($requestBody->content['application/json']->schema, '../%className%', '%className%', 'auto');
		$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
		$generator->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
		$generator->make();
	}
}