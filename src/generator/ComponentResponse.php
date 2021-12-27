<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Response;
use Infira\omg\Generator;
use Infira\omg\Config;
use Infira\omg\templates\Objekt;
use Infira\omg\templates\ClassTemplate;

class ComponentResponse extends Generator
{
	public function __construct(string $name)
	{
		parent::__construct("/component/response/$name", "/components/responses/",ClassTemplate::class);
	}
	
	public function make(Response $response)
	{
		$contentType = $this->getContentType($response);
		$generator = $this->getGenerator($response->content[$contentType]->schema, '../%className%', '%className%', 'auto');
		$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
		$generator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
		$generator->make();
	}
}