<?php

namespace Infira\omg\templates;


use Nette\PhpGenerator\ClassType;
use Infira\omg\Config;
use Nette\PhpGenerator\Literal;

class Operation extends ClassTemplate
{
	public function __construct(ClassType $class, object $phpNamespace)
	{
		parent::__construct($class, $phpNamespace);
		$this->class->setAbstract(true);
		
		$this->addProperty('method')->addComment('@var string')->setPrivate();
		$this->addProperty('path')->addComment('@var string')->setPrivate();
		$this->addProperty('operationID')->addComment('@var string')->setPrivate();
		$this->addProperty('responses')->addComment('@var array')->setValue([])->setPrivate();
		$this->addProperty('activeResponse')->addComment('@var null|\\stdClass')->setValue(null)->setPrivate();
		
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
		$constructor->addEqBodyLine('$this->method', '$method');
		$constructor->addEqBodyLine('$this->path', '$path');
		$constructor->addEqBodyLine('$this->operationID', '$operationID');
		
		$registerResponse = $this->createMethod('registerResponse');
		$registerResponse->setProtected();
		$registerResponse->addParameter('httpCode')->setType('string');
		$registerResponse->addParameter('class')->setType('string');
		$registerResponse->addParameter('contentType')->setType('string');
		$registerResponse->addEqBodyLine('$name', new Literal('"res$httpCode"'));
		$registerResponse->addEqBodyLine('$this->responses[$name]', new Literal('[\'class\' => $class, \'httpCode\' => $httpCode, \'headers\' => [\'Content-Type\' => $contentType]]'));
		
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
			$this->import('\Illuminate\Http\Request', 'Request');
			$toResponse->addComment('@param Request $request');
			$toResponse->addComment('@return Response');
			$toResponse->addParameter('request');
			$toResponse->addBodyLine('return $this->getResponse();');
		}
		
		$setResponse = $this->createMethod('setResponse');
		$setResponse->setReturnType('self');
		$setResponse->addParameter('httpCode')->setType('int');
		$setResponse->addParameter('content');
		$setResponse->setBody('if ($this->activeResponse) {
	$this->error(\'active response is already set\');
}
$params                         = $this->getResponseParams($httpCode);
$this->activeResponse           = new \stdClass();
$this->activeResponse->httpCode = $httpCode;
$this->activeResponse->headers  = $params[\'headers\'];

if (!is_object($content)) {
	$class   = $params[\'class\'];
	$content = new $class($content);
}
$this->activeResponse->content = $content;


return $this;');
		
		$getResponse = $this->createMethod('getResponse');
		$getResponse->setReturnType('\Symfony\Component\HttpFoundation\Response');
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
		
		
		$error = $this->createMethod('error');
		$error->addParameter('msg')->setType('string');
		$error->addBodyLine('throw new \Exception($msg)');
	}
	
	public function beforeFinalize() {}
}