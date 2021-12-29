<?php

namespace Infira\omg\generator;

use Infira\omg\templates\ClassTemplate as PathResponseTpl;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\{Schema, Header};
use Infira\omg\Omg;
use Infira\omg\helper\Utils;

/**
 * @property-read PathResponseTpl $tpl
 */
class PathResponse extends Response
{
	/**
	 * @param Reference|Schema|Response $resource
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return void
	 */
	public function beforeMake($resource)
	{
	
	}
	
	/**
	 * @param Header[] $headers
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return void
	 */
	private function addHeaderMethods(array $headers)
	{
		foreach ($headers as $name => $header) {
			if ($header instanceof Reference and Omg::isComponentHeader($header->getReference())) {
				$header = $header->resolve();
			}
			$setHeader = $this->tpl->createMethod(Utils::methodName(str_replace('-', '_', 'set-' . $name . 'Header')), $header->description);
			$setHeader->addTypeParameter('value', $header->schema->type);
			$setHeader->addParamComment('value', $header->schema->type);
			$setHeader->addBodyLine('$this->setHeader(\'' . $name . '\',$value)');
			$setHeader->addBodyLine('return $this');
			$setHeader->setReturnType('self');
		}
	}
}