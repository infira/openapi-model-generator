<?php

namespace Infira\omg\templates\libs;


use Nette\PhpGenerator\ClassType;
use Infira\omg\Config;
use Infira\omg\Omg;
use Infira\omg\helper\Utils;
use Infira\omg\templates\ClassTemplate;

class Operation extends ClassTemplate
{
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->class->setAbstract(true);
		
		$this->addPropertyType('method', '?string')->setPrivate();
		$this->addPropertyType('path', '?string')->setPrivate();
		$this->addPropertyType('operationID', '?string')->setPrivate();
		$this->addPropertyType('activeResponse', '?\\' . Omg::getLibPath('Response'))->setPrivate();
		
		if (Config::$laravel) {
			$this->import('\Illuminate\Contracts\Support\Responsable', 'Responsable');
			$this->addImplement('\Illuminate\Contracts\Support\Responsable');
		}
		
		$this->import('\Symfony\Component\HttpFoundation\Response', 'Response');
		$this->createMethods();
	}
	
	private function createMethods()
	{
		$this->createConstruct();
		$this->createHasRequestBody();
		$this->createGetOperationID();
		$this->createIs();
		$this->createGetMethod();
		$this->createGetPath();
		$this->createToResponse();
		$this->createSetPathResponse();
		$this->createGetResponse();
		$this->createPathResponse();
		$this->createGetModel();
		$this->createTError();
	}
	
	private function createConstruct()
	{
		$method = $this->createMethod('__construct');
		$method->addParameter('method')->setType('string')->setNullable(true);
		$method->addParameter('path')->setType('string')->setNullable(true);
		$method->addParameter('operationID')->setType('string')->setNullable(true);
		$method->addEqBodyLine('$this->method', Utils::literal('$method'));
		$method->addEqBodyLine('$this->path', Utils::literal('$path'));
		$method->addEqBodyLine('$this->operationID', Utils::literal('$operationID'));
		$method->addEqBodyLine('$this->activeResponse', null);
	}
	
	private function createGetResponse()
	{
		$method = $this->createMethod('getResponse');
		$method->setFinal(true);
		$method->setReturnType('\Symfony\Component\HttpFoundation\Response', 'Response');
		$method->setBody('if (!$this->activeResponse) {
	$this->tError(\'Response not set\');
}
$body = null;
if ($this->activeResponse->getContentType() == \'application/json\') {
	$body = json_encode($this->activeResponse->getContent()->getData(true));
}
else {
	$this->tError(\'Content type no implemented\');
}
return new Response($body, $this->activeResponse->getStatus(), $this->activeResponse->getHeaders());');
	}
	
	private function createGetModel()
	{
		$method = $this->createMethod('getModel');
		$method->setProtected(true);
		$method->addParameter('class')->setType('string');
		$method->addClassParameter('fill');
		$method->addBody('if (is_callable($fill)) {
	return $fill(new $class());
}
elseif (is_object($fill) AND $fill instanceof $class) {
	return $fill;
}

return new $class($fill);');
	}
	
	private function createTError()
	{
		$method = $this->createMethod('tError');
		$method->setProtected(true);
		$method->addParameter('msg')->setType('string');
		$method->addBodyLine('throw new \Exception($msg)');
	}
	
	private function createSetPathResponse()
	{
		$method = $this->createMethod('setPathResponse');
		$method->setProtected(true);
		$method->setReturnType('self');
		$method->addParameter('response')->setType(Omg::getLibPath('Response'));
		$method->addBodyLine('$this->activeResponse = $response');
		$method->addBodyLine('return $this');
	}
	
	private function createToResponse()
	{
		if (Config::$laravel) {
			$method = $this->createMethod('toResponse');
			$method->setFinal(true);
			$this->import('\Illuminate\Http\Request', 'Request');
			$method->addParamComment('request', 'Request');
			$method->setReturnType('\Symfony\Component\HttpFoundation\Response', 'Response');
			$method->addParameter('request');
			$method->addBodyLine('return $this->getResponse();');
		}
	}
	
	private function createGetPath()
	{
		$getPmethodth = $this->createMethod('getPath');
		$getPmethodth->setFinal(true)->setReturnType('string');
		$getPmethodth->addBodyLine('return $this->path');
	}
	
	private function createGetMethod()
	{
		$method = $this->createMethod('getMethod');
		$method->setFinal(true)->setReturnType('string');
		$method->addBodyLine('return $this->method');
	}
	
	private function createIs()
	{
		$method = $this->createMethod('is');
		$method->addParameter('operationID')->setType('string');
		$method->setFinal(true)->setReturnType('bool');
		$method->addBodyLine('return $this->operationID === $operationID;');
	}
	
	private function createGetOperationID()
	{
		$method = $this->createMethod('getOperationID');
		$method->setFinal(true)->setReturnType('string');
		$method->addBodyLine('return $this->operationID;');
	}
	
	private function createHasRequestBody()
	{
		$method = $this->createMethod('hasRequestBody');
		$method->setFinal(true)->setReturnType('bool');
		$method->addBodyLine('return isset($this->input)');
	}
	
	private function createPathResponse()
	{
		//$this->importLib('Response', 'PathResponse');
		$method = $this->createMethod('createPathResponse');
		$method->setProtected();
		$method->addTypeParameter('status', 'string');
		$method->addTypeParameter('responseClass', 'string');
		$method->addTypeParameter('model', 'object');
		$method->addClassParameter('fill');
		//$method->setReturnType(Omg::getLibPath('Response'),'PathResponse');
		$method->setBody('if (is_object($fill) and $fill instanceof $responseClass) {
    $response = $fill;
}
else {
    $response = new $responseClass();
    $response->setContent($model);
}
$response->setStatus($status);

return $response;');
	}
	
	public function beforeFinalize() {}
	
	
}