<?php

namespace Infira\omg\templates;


use Infira\omg\helper\Utils;
use Infira\omg\Config;

class PathOperation extends Class__Construct
{
	public function registerHttpResponse(string $httpCode, string $responseClass, string $modelClass, string $contentType)
	{
		$statusName = Config::getHttpStatusName($httpCode);
		$this->importLib('Storage');
		
		$responseMethodName = "res_$httpCode";
		if ($httpCode !== $statusName) {
			$responseMethodName = $statusName;
		}
		$responseMethodName = Utils::methodName($responseMethodName);
		$getModelMethodName = sprintf('get%sModel', ucfirst($statusName));
		
		$this->createResponseMethod($httpCode, $responseClass, $contentType, $responseMethodName, $getModelMethodName);
		$this->createModelMethod($getModelMethodName, $responseMethodName, $modelClass);
	}
	
	public function createModelMethod(string $methodName, string $responseMethodName, string $modelClass)
	{
		$modelAlias = sprintf('%sModel', Utils::className($responseMethodName));
		$types[]    = 'array';
		$types[]    = '\stdClass';
		$types[]    = 'callable';
		$types[]    = 'string';
		$this->import($modelClass, $modelAlias);
		
		
		$method = $this->createMethod($methodName);
		$method->addTypeParameter('fill', ...array_merge($types, [$modelClass]))->setDefaultValue(Utils::literal('Storage::NOT_SET'));
		$method->setReturnType($modelClass, false);
		$method->addBodyLine(sprintf('return $this->getModel($fill,%s);', Utils::extractClass($modelAlias)));
		
		if (Config::$phpVersion <= 7.3) {
			//$method->addComment($comment);
			$method->addParamComment('fill', ...array_merge($types, [$modelAlias]));
			$method->setReturnType('self', true);
			$method->addComment('@return %s', Utils::extractName($modelAlias));
		}
		
	}
	
	public function createResponseMethod(string $httpCode, string $responseClass, string $contentType, string $methodName, string $getModelMethodName)
	{
		$httpCodeParam = $httpCode == 'default' ? '$httpCode' : $httpCode;
		$comment       = $httpCode == 'default' ? 'set response by $httpCode' : "set response(httpCode=$httpCode)";
		
		$responseAlias = sprintf('%sResponse', Utils::className($methodName));
		$types[]       = 'array';
		$types[]       = '\stdClass';
		$types[]       = 'callable';
		$types[]       = 'string';
		$this->import($responseClass, $responseAlias);
		
		$method = $this->createMethod($methodName);
		$method->addBodyLine(sprintf('return $this->setResponse(%s, $this->%s($fill) ,\'%s\')', $httpCodeParam, $getModelMethodName, $contentType));
		if ($httpCode == 'default') {
			$method->addTypeParameter('httpCode', 'int');
		}
		
		$method->addTypeParameter('fill', ...array_merge($types, [$responseClass]))->setDefaultValue(Utils::literal('Storage::NOT_SET'));
		$rtComment = false;
		if (Config::$phpVersion <= 7.3) {
			$method->addComment($comment);
			$method->addParamComment('fill', ...array_merge($types, [$responseAlias]));
			$method->setReturnType('self', true);
			$rtComment = true;
		}
		$method->setReturnType('self', $rtComment);
	}
	
}