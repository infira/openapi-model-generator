<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Response;
use Infira\omg\Config;
use Infira\omg\Omg;
use cebe\openapi\spec\{Reference, Response as ResponseSepc};

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
		parent::__construct("/components/responses/$name", "/components/responses/$name");
		$this->response = $response;
		$this->name     = $name;
		$this->tpl->extendLib('Response');
		$this->beforeMake($response);
	}
	
	/**
	 * @inheritDoc
	 */
	public function beforeMake($resource)
	{
		$this->makeContent($resource);
		
		parent::beforeMake($resource);
	}
	
	private function makeContent(ResponseSepc $response)
	{
		$contentType = Omg::getContentType($response);
		$content     = $response->content[$contentType]->schema;
		if ($content instanceof Reference) {
			$this->setContentMethod(Omg::getReferenceClassPath($content->getReference()));
		}
		else {
			$generator              = $this->getGenerator($content, Omg::getComponentResponseContentNsPart(), Omg::getComponentResponseContentNsPart());
			$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
			$generator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
			$generator->make();
			$this->setContentMethod($generator->getFullClassPath());
		}
	}
	
	private function setContentMethod(string $contentClass)
	{
		$this->tpl->import($contentClass);
		$this->tpl->addDocPropertyComment('content', "?" . $contentClass, null);
		$set = $this->tpl->createMethod('setContent');
		$set->addParameter('content')->setType($contentClass);
		$set->addBodyLine('parent::setContent($content)');
		
		$get = $this->tpl->createMethod('getContent');
		$get->setReturnType('?' . $contentClass, false);
		$get->addBodyLine('return $this->content');
	}
}