<?php

namespace Infira\omg\templates;


use Nette\PhpGenerator\ClassType;
use Infira\omg\Config;
use Infira\omg\Omg;
use Infira\omg\helper\Utils;

class Operation extends ClassTemplate
{
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->class->setAbstract(true);
		
		$this->addPropertyType('method', '?string')->setPrivate();
		$this->addPropertyType('path', '?string')->setPrivate();
		$this->addPropertyType('operationID', '?string')->setPrivate();
		$this->addPropertyType('activeResponse', '?\\stdClass')->setPrivate();
		
		if (Config::$laravel) {
			$this->import('\Illuminate\Contracts\Support\Responsable', 'Responsable');
			$this->addImplement('\Illuminate\Contracts\Support\Responsable');
		}
		
		$this->import('\Symfony\Component\HttpFoundation\Response', 'Response');
		$this->createMethods();
	}
	
	private function createMethods()
	{
		$constructor = $this->createMethod('__construct');
		$constructor->addParameter('method')->setType('string')->setNullable(true);
		$constructor->addParameter('path')->setType('string')->setNullable(true);
		$constructor->addParameter('operationID')->setType('string')->setNullable(true);
		$constructor->addEqBodyLine('$this->method', Utils::literal('$method'));
		$constructor->addEqBodyLine('$this->path', Utils::literal('$path'));
		$constructor->addEqBodyLine('$this->operationID', Utils::literal('$operationID'));
		
		$hasRequestBody = $this->createMethod('hasRequestBody');
		$hasRequestBody->setFinal(true)->setReturnType('bool');
		$hasRequestBody->addBodyLine('return isset($this->input)');
		
		$getOperationID = $this->createMethod('getOperationID');
		$getOperationID->setFinal(true)->setReturnType('string');
		$getOperationID->addBodyLine('return $this->operationID;');
		
		$is = $this->createMethod('is');
		$is->addParameter('operationID')->setType('string');
		$is->setFinal(true)->setReturnType('bool');
		$is->addBodyLine('return $this->operationID === $operationID;');
		
		$getMethod = $this->createMethod('getMethod');
		$getMethod->setFinal(true)->setReturnType('string');
		$getMethod->addBodyLine('return $this->method');
		
		$getPath = $this->createMethod('getPath');
		$getPath->setFinal(true)->setReturnType('string');
		$getPath->addBodyLine('return $this->path');
		
		if (Config::$laravel) {
			$toResponse = $this->createMethod('toResponse');
			$toResponse->setFinal(true);
			$this->import('\Illuminate\Http\Request', 'Request');
			$toResponse->addParamComment('request', 'Request');
			$toResponse->setReturnType('\Symfony\Component\HttpFoundation\Response', true);
			$toResponse->addParameter('request');
			$toResponse->addBodyLine('return $this->getResponse();');
		}
		
		$setResponse = $this->createMethod('setResponse');
		$setResponse->setProtected(true);
		$setResponse->setReturnType('self', false);
		$setResponse->addParameter('httpCode')->setType('int');
		$setResponse->addParameter('content')->setType(Omg::getLibPath('Storage'));
		$setResponse->addParameter('contentType')->setType('string');
		$setResponse->setBody('if ($this->activeResponse) {
	$this->error(\'active response is already set\');
}
$this->activeResponse           = new \stdClass();
$this->activeResponse->httpCode = $httpCode;
$this->activeResponse->headers  = [\'Content-Type\' => $contentType];
$this->activeResponse->content  = $content;


return $this;');
		
		$getResponse = $this->createMethod('getResponse');
		$getResponse->setFinal(true);
		$getResponse->setReturnType('\Symfony\Component\HttpFoundation\Response', false);
		$getResponse->setBody('if (!$this->activeResponse) {
	$this->error(\'Response not set\');
}
$body = null;
if ($this->activeResponse->headers[\'Content-Type\'] == \'application/json\') {
	$body = json_encode($this->activeResponse->content->getData());
}
else {
	$this->error(\'Content type no implemented\');
}
$res = new Response(
	$body,
	$this->activeResponse->httpCode,
	$this->activeResponse->headers,
);

return $res;');
		
		$getModel = $this->createMethod('getModel');
		$getModel->setProtected(true);
		$param = $getModel->addParameter('fill');
		if (Config::$phpVersion > 7.3) {
			$param->setType('array|object|null');
		}
		else {
			$getModel->addParamComment('fill', 'array|object|null');
		}
		$getModel->addParameter('class')->setType('string');
		$getModel->addBody('if (is_callable($fill))
{
	return $fill(new $class(null));
}
elseif (is_object($fill) AND $fill instanceof $class)
{
	return $fill;
}

return new $class($fill);');
		
		
		$error = $this->createMethod('error');
		$error->addParameter('msg')->setType('string');
		$error->addBodyLine('throw new \Exception($msg)');
	}
	
	public function beforeFinalize() {}
}