<?php

namespace Infira\omg\templates;


use Infira\omg\helper\Utils;
use Infira\omg\Config;

class PathOperation extends Class__Construct
{
	public function registerHttpResponse(string $httpCode, string $responseClass, string $modelClass)
	{
		$statusName = Config::getHttpStatusName($httpCode);
		$this->importLib('Storage');
		
		$responseMethodName = "res_$httpCode";
		if ($httpCode !== $statusName) {
			$responseMethodName = $statusName;
		}
		$responseMethodName = Utils::methodName($responseMethodName);
		$getModelMethodName = sprintf('get%sModel', ucfirst($statusName));
		
		$this->createResponseMethod($httpCode, $responseClass, $responseMethodName, $getModelMethodName);
		$this->createModelMethod($getModelMethodName, $responseMethodName, $modelClass);
	}
	
	public function createResponseMethod(string $httpCode, string $responseClass, string $methodName, string $getModelMethodName)
	{
		$httpCodeParam = $httpCode == 'default' ? '$httpCode' : $httpCode;
		$comment       = $httpCode == 'default' ? 'set response by $httpCode' : "set response(httpCode=$httpCode)";
		
		$modelAlias    = sprintf('%sModel', Utils::className($methodName));
		$responseAlias = sprintf('%sResponse', Utils::className($methodName));
		
		$method = $this->createMethod($methodName, $comment);
		$method->addBodyLine(sprintf('return $this->setPathResponse($this->createPathResponse(%s,%s::class,%s::class,$fill));', $httpCodeParam, $responseAlias, $modelAlias));
		$method->addBodyLine();
		if ($httpCode == 'default') {
			$method->addTypeParameter('httpCode', 'int');
		}
		
		$this->import($responseClass, $responseAlias);
		$method->addClassParameter('fill', $responseAlias);
		$method->setReturnType('self', true);
	}
	
	public function createModelMethod(string $methodName, string $responseMethodName, string $modelClass)
	{
		$modelAlias = sprintf('%sModel', Utils::className($responseMethodName));
		
		$this->import($modelClass, $modelAlias);
		$method = $this->createMethod($methodName);
		$method->addClassParameter('fill', $modelAlias);
		$method->setReturnType($modelClass, false);
		$method->addBodyLine(sprintf('return $this->getModel($fill,%s);', Utils::extractClass($modelAlias)));
		$method->addComment('@return %s', Utils::extractName($modelAlias));
		$method->setReturnType($modelClass, false);
		
	}
	
}