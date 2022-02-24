<?php

namespace Infira\omg\helper;

class ParametersSpec
{
	/**
	 * @var \cebe\openapi\spec\Parameter[]
	 */
	private $parameters;
	
	/**
	 * @param \cebe\openapi\spec\Parameter[] $parameters
	 */
	public function __construct(array $parameters)
	{
		$this->parameters = $parameters;
	}
	
	/**
	 * @return \cebe\openapi\spec\Parameter[]
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}
}