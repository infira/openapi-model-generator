<?php

namespace Infira\omg\templates;


class PathOperation extends Objekt
{
	public function registerHttpResponse(string $httpCode, string $class, string $contentType)
	{
		$resName = "res$httpCode";
		
		$this->addConstructorLine('$this->registerResponse(\'%s\',\'%s\',\'%s\');', $httpCode, $class, $contentType);
		$this->addDocProperty($resName, 'singleClass', $class, false, "http code $httpCode class");
		
		$bodyLines   = [
			'$this->activeResponseHttpCode = \'' . $httpCode . '\';',
		];
		$bodyLines[] = sprintf('$this->%s = $content;', "res$httpCode");
		$bodyLines[] = 'return $this;';
		$this->addMethod($resName, $class, $class, 'content', false, 'self', "http operation(httpCode=$httpCode) data model", $bodyLines);
		//$method = $this->tpl->createMethod("set$httpCode");
		//$method->setReturnType('self');
	}
}