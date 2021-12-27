<?php

namespace Infira\omg\templates;


use Infira\omg\helper\Utils;

class PathOperation extends Objekt
{
	public function registerHttpResponse(string $httpCode, string $class, string $contentType)
	{
		$methodName = "res$httpCode";
		$params     = ['content' => $class];
		$bodyLines  = [sprintf('return $this->setResponse(%s, $content)', $httpCode)];
		
		$comment = "set response(httpCode=$httpCode)";
		if ($httpCode == "200") {
			$methodName = "success";
		}
		elseif ($httpCode == 'default') {
			$methodName = 'default';
			$params     = array_merge(['httpCode' => 'int'], $params);
			$bodyLines  = ['return $this->setResponse($httpCode, $content)'];
			$comment    = "set response";
		}
		
		$this->import($class);
		$this->addConstructorLine('$this->registerResponse(\'%s\',%s,\'%s\');', $httpCode, Utils::extractClass($class), $contentType);
		$this->addDocPropertyComment($methodName, Utils::extractName($class), "http code $httpCode class");
		
		$method = $this->createMethod($methodName);
		$method->addComment($comment);
		$method->addBodyLine(...$bodyLines);
		$method->addParameters($params);
		
		
		$method->setReturnType('self', true);
	}
}