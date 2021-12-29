<?php

namespace Infira\omg\templates\libs;


use Nette\PhpGenerator\ClassType;
use Infira\omg\Config;
use Infira\omg\templates\ClassTemplate;
use Infira\omg\Omg;

class Response extends ClassTemplate
{
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->addPropertyType('headers', 'array')->setValue([])->setPrivate();
		
		if (Config::$laravel) {
			//$this->extend('\Symfony\Component\HttpFoundation\Response', 'LaravelResponse');
		}
		
		//$this->import('\Symfony\Component\HttpFoundation\Response', 'Response');
		$this->createSetHeaders();
		$this->createSetHeader();
		$this->createContentMethods();
	}
	
	private function createSetHeaders()
	{
		$method = $this->createMethod('setHeaders');
		$method->addParameter('headers', [])->setType('array');
		$method->addBody('foreach ($headers as $header => $value)
{
	$this->setHeader($header, $value);
}
');
	}
	
	private function createSetHeader()
	{
		$method = $this->createMethod('setHeader');
		$method->addParameter('header')->setType('string');
		$method->addParameter('value')->setType('string');
		$method->addBodyLine('$this->headers[$header] = $value');
	}
	
	private function createContentMethods()
	{
		$this->importLib('Storage');
		$this->addPropertyType('content', "?" . Omg::getLibPath('Storage'))->setPrivate(true)->setValue(null);
		$set = $this->createMethod('setContent');
		$set->addParameter('content');//->setType(Omg::getLibPath('Storage'));
		$set->addParamComment('content', 'Storage');
		$set->addBodyLine('$this->content = $content');
		
		$get = $this->createMethod('getContent');
		$get->setReturnType('?' . Omg::getLibPath('Storage'), false);
		$get->addBodyLine('return $this->content');
	}
	
	public function beforeFinalize() {}
}