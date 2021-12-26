<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Response;
use Infira\omg\Generator;
use Infira\omg\Config;

class ComponentResponse extends Generator
{
	public function __construct(string $name)
	{
		parent::__construct("/component/response/$name", "/components/responses/");
	}
	
	public function make(Response $response)
	{
		$contentType = $this->getContentType($response);
		$generator = $this->getGenerator($response->content[$contentType]->schema, '../%className%', '%className%', 'auto');
		$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
		$generator->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
		$generator->make();
	}
}