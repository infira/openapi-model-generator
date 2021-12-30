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
		$responseMethodName           = Utils::methodName($responseMethodName);
		$getModelMethodName           = sprintf('get%sModel', ucfirst($statusName));
		$createPathResponseMethodName = sprintf('create%sResponse', ucfirst($statusName));
		
		$responseAlias = sprintf('%sResponse', Utils::className($responseMethodName));
		$modelAlias    = sprintf('%sModel', Utils::className($responseMethodName));
		$httpCodeParam = $httpCode == 'default' ? '$httpCode, ' : '';
		$comment       = $httpCode == 'default' ? 'set response by $httpCode' : "set response(httpCode=$httpCode)";
		
		$setResponseMethod = $this->createMethod($responseMethodName, $comment);
		$setResponseMethod->addBodyLine(sprintf('return $this->setPathResponse($this->%s(%s$fill));', $createPathResponseMethodName, $httpCodeParam));
		if ($httpCode == 'default') {
			$setResponseMethod->addTypeParameter('httpCode', 'int');
		}
		$this->import($responseClass, $responseAlias);
		$setResponseMethod->addClassParameter('fill', $responseAlias);
		$setResponseMethod->setReturnType('self', 'self');
		
		
		$createPathResponse = $this->createMethod($createPathResponseMethodName, $comment);
		$httpCodeParam      = $httpCode == 'default' ? '$httpCode' : $httpCode;
		$createPathResponse->addBodyLine(sprintf('return $this->createPathResponse(%s,%s::class,$this->%s($fill));', $httpCodeParam, $responseAlias, $getModelMethodName));
		if ($httpCode == 'default') {
			$createPathResponse->addTypeParameter('httpCode', 'int');
		}
		$createPathResponse->addClassParameter('fill', $responseAlias);
		$createPathResponse->setReturnType($responseClass, $responseAlias);
		
		
		$this->import($modelClass, $modelAlias);
		$getModelMethod = $this->createMethod($getModelMethodName);
		$getModelMethod->addClassParameter('fill', $modelAlias);
		$getModelMethod->setReturnType($modelClass);
		$getModelMethod->addBodyLine(sprintf('return $this->getModel(%s, $fill);', Utils::extractClass($modelAlias)));
		$getModelMethod->addReturnComment(Utils::extractName($modelAlias));
		$getModelMethod->setReturnType($modelClass);
	}
	
}