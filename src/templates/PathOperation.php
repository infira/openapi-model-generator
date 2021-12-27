<?php

namespace Infira\omg\templates;


use Infira\omg\helper\Utils;

class PathOperation extends Objekt
{
	public function registerHttpResponse(string $httpCode, string $class, string $contentType)
	{
		$methodName = "res$httpCode";
		$params     = $fillParams = ['fill' => $class];
		
		$comment       = "set response(httpCode=$httpCode)";
		$httpCodeParam = $httpCode;
		if ($httpCode == "200") {
			$methodName = "success";
		}
		elseif ($httpCode == 'default') {
			$methodName    = 'default';
			$params        = array_merge(['httpCode' => 'int'], $params);
			$comment       = "set response";
			$httpCodeParam = '$httpCode';
		}
		$getMethodName = sprintf('get%sModel', ucfirst($methodName));
		
		$this->import($class);
		$this->addDocPropertyComment($methodName, Utils::extractName($class), "http code $httpCode class");
		
		$method = $this->createMethod($methodName, $comment);
		$method->addBodyLine(sprintf('return $this->setResponse(%s, $this->%s($fill) ,\'%s\')', $httpCodeParam, $getMethodName, $contentType));
		$method->addParameters($params);
		$method->setReturnType('self', true);
		
		$getMethod = $this->createMethod($getMethodName);
		$getMethod->addParameters($fillParams);
		$getMethod->setReturnType($class);
		$getMethod->addBodyLine(sprintf('return $this->getModel($fill,%s);', Utils::extractClass($class)));
	}
}