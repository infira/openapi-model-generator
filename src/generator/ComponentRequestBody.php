<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use Infira\omg\Config;
use cebe\openapi\spec\RequestBody;
use Infira\omg\templates\ClassTemplate;

class ComponentRequestBody extends Generator
{
	public function __construct(string $name)
	{
		parent::__construct("/component/requestBodies/$name", "/components/requestBodies/", ClassTemplate::class);
	}
	
	public function make(RequestBody $requestBody)
	{
		$contentType            = $this->getContentType($requestBody);
		$generator              = $this->getGenerator($requestBody->content[$contentType]->schema, '../%className%', '%className%', 'auto');
		$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
		$generator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
		$generator->make();
	}
}