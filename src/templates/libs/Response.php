<?php

namespace Infira\omg\templates\libs;


use Nette\PhpGenerator\ClassType;
use Infira\omg\Config;
use Infira\omg\Omg;
use Infira\omg\templates\Class__Construct;

class Response extends Class__Construct
{
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->addPropertyType('headers', 'array')->setValue([])->setPrivate();
		$this->setAbstract(true);
		
		$this->constructor->addTypeParameter('headers', 'array')->setDefaultValue([]);
		$this->constructor->addBodyLine('$this->setHeaders($headers)');
		
		if (Config::$laravel) {
			//$this->extend('\Symfony\Component\HttpFoundation\Response', 'LaravelResponse');
		}
		
		//$this->import('\Symfony\Component\HttpFoundation\Response', 'Response');
		$this->createHeaders();
		$this->createSetHeader();
		$this->createContentMethods();
		$this->createStatus();
		$this->createContentType();
	}
	
	private function createHeaders()
	{
		$set = $this->createMethod('setHeaders');
		$set->addParameter('headers', [])->setType('array');
		$set->addBody('foreach ($headers as $header => $value) {
	$this->setHeader($header, $value);
}
');
		
		$get = $this->createMethod('getHeaders')->setReturnType('array', false);
		$get->addBodyLine('return $this->headers');
	}
	
	private function createSetHeader()
	{
		$set = $this->createMethod('setHeader')->setReturnType('self', false);
		$set->addParameter('header')->setType('string');
		$set->addParameter('value')->setType('string');
		$set->addBodyLine('$this->headers[$header] = $value');
		$set->addBodyLine('return $this');
		
		$get = $this->createMethod('getHeader')->setReturnType('?string', false);
		$get->addParameter('header')->setType('string');
		$get->addBodyLine('return $this->headers[$header] ?? null');
	}
	
	private function createContentMethods()
	{
		$this->importLib('Storage');
		$this->addPropertyType('content', "?" . Omg::getLibPath('Storage'))->setPrivate(true)->setValue(null);
		
		$set = $this->createMethod('doSetContent');
		$set->setProtected(true);
		$set->addParameter('content')->setType(Omg::getLibPath('Storage'));
		//$set->addParamComment('content', 'Storage');
		$set->addBodyLine('$this->content = $content');
		
		$get = $this->createMethod('doGetContent')->setReturnType('?' . Omg::getLibPath('Storage'), false);
		$get->addBodyLine('return $this->content');
	}
	
	private function createStatus()
	{
		$set = $this->createMethod('setStatus')->setReturnType('self', false);
		$set->addParameter('code')->setType('string');//->setType(Omg::getLibPath('Storage'));
		$this->addPropertyType('httpStatus', '?string')->setValue(null);
		$set->addBodyLine('$this->httpStatus = $code');
		$set->addBodyLine('return $this');
		
		$get = $this->createMethod('getStatus')->setReturnType('?int', false);
		$get->addBodyLine('return $this->httpStatus');
		$get->setReturnType('string', false);
	}
	
	private function createContentType()
	{
		$set = $this->createMethod('setContentType')->setReturnType('self', false);
		$set->addParameter('value')->setType('string');//->setType(Omg::getLibPath('Storage'));
		$set->addBodyLine('$this->setHeader(\'Content-Type\', $value)');
		$set->addBodyLine('return $this');
		
		$get = $this->createMethod('getContentType')->setReturnType('?string', false);
		$get->addBodyLine('return $this->getHeader(\'Content-Type\')');
	}
	
	public function beforeFinalize() {}
}