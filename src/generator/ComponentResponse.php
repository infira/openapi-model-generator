<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Response;
use cebe\openapi\spec\{Response as ResponseSepc};

class ComponentResponse extends \Infira\omg\generator\Response
{
	/**
	 * @var ResponseSepc
	 */
	private $response;
	/**
	 * @var string
	 */
	private $name;
	
	public function __construct(string $name, Response $response)
	{
		parent::__construct("/components/responses/$name", "#/components/responses/$name");
		$this->response = $response;
		$this->name     = $name;
		$this->beforeMake($response);
	}
}