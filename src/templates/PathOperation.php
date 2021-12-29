<?php

namespace Infira\omg\templates;


use Infira\omg\helper\Utils;
use Infira\omg\Config;

class PathOperation extends Objekt
{
	public function registerHttpResponse(string $httpCode, string $class, string $contentType)
	{
		$statusName = Config::getHttpStatusName($httpCode);
		$this->importLib('Storage');
		
		$methodName    = "res_$httpCode";
		$comment       = "set response(httpCode=$httpCode)";
		$httpCodeParam = $httpCode;
		
		if ($httpCode !== $statusName) {
			$methodName = $statusName;
		}
		$methodName = Utils::methodName($methodName);
		if ($httpCode == 'default') {
			$comment       = 'set response by $httpCode';
			$httpCodeParam = '$httpCode';
		}
		$getMethodName = sprintf('get%sModel', ucfirst($statusName));
		$classType     = "?$class";
		
		$this->import($class);
		$this->addDocPropertyComment($methodName, Utils::extractName($class), "http code $httpCode class");
		
		$method = $this->createMethod($methodName, $comment);
		$method->addBodyLine(sprintf('return $this->setResponse(%s, $this->%s($fill) ,\'%s\')', $httpCodeParam, $getMethodName, $contentType));
		if ($httpCode == 'default') {
			$method->addTypeParameter('httpCode', 'int');
		}
		$param = $method->addTypeParameter('fill', $classType, true)->setDefaultValue(Utils::literal('Storage::NOT_SET'));
		$param->setType($param->getType() . '|string');
		$method->setReturnType('self', true);
		
		$getMethod = $this->createMethod($getMethodName);
		$param     = $getMethod->addTypeParameter('fill', "$classType", true)->setDefaultValue(Utils::literal('Storage::NOT_SET'));
		$param->setType($param->getType() . '|string');
		$getMethod->setReturnType($class, true);
		$getMethod->addBodyLine(sprintf('return $this->getModel($fill,%s);', Utils::extractClass($class)));
	}
}