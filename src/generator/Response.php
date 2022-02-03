<?php

namespace Infira\omg\generator;

use Infira\omg\Generator;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\{Response as ResponseSepc, Schema, Header};
use Infira\omg\Omg;
use Infira\omg\helper\Utils;
use Infira\omg\templates\Class__Construct;

/**
 * @property-read Class__Construct $tpl
 */
class Response extends Generator
{
	private $contentClass;
	
	public function __construct(string $namespace, string $schemaLocation)
	{
		parent::__construct($namespace, $schemaLocation, Class__Construct::class);
		$this->tpl->extendLib('Response');
	}
	
	/**
	 * @param Reference|Schema|Response $resource
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return void
	 */
	public function beforeMake($resource)
	{
		if ($resource instanceof Reference) {
			$headers = $resource->resolve()->headers;
		}
		elseif ($resource instanceof \cebe\openapi\spec\Response) {
			$headers = $resource->headers;
		}
		else {
			Omg::error('unknown type');
		}
		
		$contentType = Omg::getContentType($resource);
		$this->tpl->constructor->addBodyLine('parent::__construct([])');
		$this->tpl->constructor->addBodyLine('$this->setContentType(\'' . $contentType . '\')');
		
		$this->addHeaderMethods($headers);
		$this->makeContent($resource);
	}
	
	/**
	 * @param Header[] $headers
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return void
	 */
	private function addHeaderMethods(array $headers)
	{
		foreach ($headers as $name => $header) {
			if ($header instanceof Reference and Omg::isComponentHeader($header)) {
				$header = $header->resolve();
			}
			$setHeader = $this->tpl->createMethod(Utils::methodName(str_replace('-', '_', 'setHeader-' . $name)), $header->description);
			$setHeader->addTypeParameter('value', $header->schema->type);
			$setHeader->addBodyLine('$this->setHeader(\'' . $name . '\',$value)');
			$setHeader->addBodyLine('return $this');
			$setHeader->setReturnType('self');
			if ($header->schema->default) {
				$this->tpl->constructor->addBodyLine(sprintf('$this->setHeader(\'%s\',%s)', $name, $header->schema->default));
			}
			
		}
	}
	
	public function makeContent(ResponseSepc $response)
	{
		$contentType = Omg::getContentType($response);
		$content     = $response->content[$contentType]->schema;
		if ($content instanceof Reference) {
			$this->contentClass = Omg::getReferenceClassPath($content);
		}
		else {
			$generator = $this->getGenerator($content, Omg::getComponentResponseContentNsPart(), Omg::getComponentResponseContentNsPart());
			$generator->make();
			$this->contentClass = $generator->getFullClassPath();
		}
		$this->setContentMethod();
	}
	
	private function setContentMethod()
	{
		$contentClass = $this->contentClass;
		$this->tpl->import($contentClass, 'Content');
		$set = $this->tpl->createMethod('setContent');
		$this->tpl->importLib('Storage');
		$set->addClassParameter('content', 'Content');
		$set->addBodyLine('$this->content = $this->getModel(Content::class, $content)');
		
		$get = $this->tpl->createMethod('getContent');
		$get->setReturnType("?" . $contentClass);
		$get->addBodyLine('return $this->content');
	}
	
	public function getContentClass(): string
	{
		return $this->contentClass;
	}
}