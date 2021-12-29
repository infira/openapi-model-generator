<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Response;
use Infira\omg\Config;
use Infira\omg\Omg;
use cebe\openapi\spec\Schema;
use Infira\omg\Generator;
use Infira\omg\helper\Utils;

/**
 * @property-read \Infira\omg\templates\PathOperation $tpl
 */
class PathOperation extends Generator
{
	private $path            = '';
	private $method          = '';
	private $responseClasses = [];
	
	/**
	 * @var Operation
	 */
	protected $operation;
	
	public final function __construct(string $path, string $method, Operation $operation)
	{
		$this->operation = $operation;
		$this->method    = $method;
		$this->path      = $path;
		
		
		$templateParts = explode('/', Config::$pathNamespaceTemplate);
		
		$ns = ['path'];
		foreach ($templateParts as $part) {
			$ns = array_merge($ns, $this->parseTemplatePart($part));
		}
		parent::__construct(Utils::ns(...$ns)->get(), "/$path/$method", \Infira\omg\templates\PathOperation::class);
		//debug([$this->path => [Config::$pathNamespaceTemplate, $this->getFullClassPath()]]);
		//debug('----------------------------------- THE END ----------------------------------------------------------------');
		
		
		$aOperationID = $operation->operationId ? "'$operation->operationId'" : 'null';
		$aPath        = $path ? "'$path'" : 'null';
		$this->tpl->addConstructorLine('parent::__construct(\'%s\', %s, %s);', $method, $aPath, $aOperationID);
		
		$this->tpl->addComment('Operation path %s %s', strtoupper($method), $path);
		$this->tpl->import(Omg::getOperationPath(), 'Operation');
		$this->tpl->setExtends(Omg::getOperationPath());
		if ($traits = Config::getOperationTraits()) {
			foreach ($traits as $trait) {
				$this->tpl->addTrait($trait);
			}
		}
		if ($traits = Config::getOperationInterfaces()) {
			foreach ($traits as $trait) {
				$this->tpl->addImplement($trait);
			}
		}
	}
	
	private function parseTemplatePart(string $part)
	{
		$output = [];
		//debug(['$part' => $part]);
		if (preg_match_all('/\{(.+?)\}/m', $part, $varContentMatches)) {
			//			debug(['$varContentMatches' => $varContentMatches[1]]);
			foreach ($varContentMatches[1] as $varMatch) {
				$output = array_merge($output, $this->parseTemplatePart($varMatch));
			}
		}
		elseif (strpos($part, '?') !== false) {
			$q = explode('?', $part);
			//			debug(['?' => [$part => $q]]);
			foreach ($q as $qk => $qv) {
				$q[$qk] = $this->parseTemplatePart($qv);
			}
			if (empty($q[0])) {
				$output = array_merge($output, $q[1]);
			}
			else {
				$output = array_merge($output, $q[0]);
			}
		}
		elseif (preg_match('/(\w+)\((.+)\)/m', $part, $functionMatches)) {
			$function = $functionMatches[1];
			if (!in_array($function, ['generateName', 'splitDotNames'])) {
				Omg::error("Unknown template function('$function')");
			}
			//debug(['$functionMatches' => $functionMatches]);
			$output = $this->parseTemplatePart($functionMatches[2]);
			//			debug(['$functionMatches' => ['match' => $functionMatches, 'parts' => $output]]);
			$output = $this->parseTemplateFunction($function, $output);
		}
		elseif (preg_match('/(\w+)?(\[.*\])/m', $part, $varMatches)) {
			//			debug(['$varMatches' => $varMatches]);
			$varName = $varMatches[1];
			$output  = $this->getVariableParts($varName);
			if (preg_match_all('/\[(.+?)\]/m', $varMatches[2], $positionMatches)) {
				//debug(['$positionMatch' => $positionMatches[1]]);
				foreach ($positionMatches[1] as $positionMatch) {
					$positions = explode(':', $positionMatch);
					if ($positions[0] == 'last') {
						$positions[0] = array_key_last($output);
					}
					elseif ($positions[0] <= 0) {
						$positions[0] = 0;
					}
					
					if (count($positions) == 1) {
						$output = [$output[$positions[0]]];
					}
					else {
						$from = $positions[0];
						$to   = $positions[1];
						if ($to == '*') {
							$to = 999;
						}
						
						$output = array_slice($output, $from, $to);
					}
					
					if (!$output) {
						break;
					}
				}
			}
		}
		else {
			$output = $this->getVariableParts($part);
			//			debug(['else' => $part]);
		}
		
		return $output;
	}
	
	private function getVariableParts(string $variable): array
	{
		switch ($variable) {
			case 'method':
				$value = $this->method;
			break;
			
			case 'operationID':
				$value = $this->operation->operationId;
			break;
			
			case 'path':
				$value = $this->path;
			break;
			
			case 'tags':
				return $this->parseParts($this->operation->tags);
			
			default:
				Omg::error("undefined template variable('$variable')");
		}
		if (empty($value)) {
			return [];
		}
		
		if ($value[0] == '/') {
			$value = substr($value, 1);
		}
		
		$parts = explode('/', $value);
		if ($variable == 'path') {
			array_walk($parts, function (&$part)
			{
				$part = $this->extractPathVariableName($part);
			});
		}
		
		return $this->parseParts($parts);
	}
	
	private function parseParts(array $parts): array
	{
		array_walk($parts, function (&$part)
		{
			if (in_array($part, ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield', 'yield', 'from'])) {
				$part = "_$part";
			}
		});
		
		return $parts;
	}
	
	private function parseTemplateFunction(string $function, array $output): array
	{
		if ($function == 'generateName') {
			return [Utils::methodName(join('_', $output))];
		}
		elseif ($function == 'splitDotNames') {
			$newOutput = [];
			foreach ($output as $p) {
				$ex = explode('.', $p);
				array_walk($ex, function (&$item)
				{
					$item = Utils::methodName($item);
				});
				$newOutput = array_merge($newOutput, $ex);
			}
			
			return $newOutput;
		}
		
		return $output;
	}
	
	public function make(): string
	{
		//parse requests
		if ($this->operation->requestBody) {
			if ($this->operation->requestBody instanceof Reference) {
				$generateFrom = $this->operation->requestBody;
				$description  = $this->operation->requestBody->resolve()->descrtipion ?? '';
			}
			else {
				$generateFrom = $this->operation->requestBody->content[Omg::getContentType($this->operation->requestBody)]->schema;
				$description  = $this->operation->requestBody->description;
			}
			$generator = $this->getGenerator($generateFrom, '../body/%className%Body', "requestBodies");
			$generator->tpl->addConstructorLine('$this->fillNonExistingWithDefaultValues = true;');
			$generator->make();
			
			$inputClass     = $generator->getFullClassPath();
			$inputClassName = Utils::extractName($inputClass);
			
			$this->tpl->import($inputClass, $inputClassName);
			$this->tpl->addConstructorLine('$this->%s = new %s;', Config::$operationInputParameterName, $inputClassName);
			$prop = $this->tpl->addPropertyType(Config::$operationInputParameterName, $inputClass);
			if ($description) {
				$prop->addComment($description);
			}
			
		}
		
		//##################### Responses
		/** @var Response $response */
		foreach ($this->operation->responses as $httpCode => $response) {
			$this->parseResponse($httpCode, $response);
		}
		
		return parent::make();
	}
	
	/**
	 * @param string                    $httpCode
	 * @param Reference|Schema|Response $resource
	 * @return void
	 */
	private function makeResponse(string $httpCode, $resource)
	{
		$generator = new PathResponse($this->ns->get('../responses/%className%' . $httpCode . 'Response'), $this->schemaLocation->get("$httpCode/response"));
		if ($resource instanceof Reference) {
			$generator->tpl->setExtends(Omg::getReferenceClassPath($resource->getReference()));
		}
		else {
			//debug(get_class($resource));
		}
		//$generator->tpl->setExtends()
		$generator->beforeMake($resource);
		$generator->make();
	}
	
	private function isPathVariable(string $part)
	{
		return preg_match('/\{(\w+)\}/m', $part);
	}
	
	private function extractPathVariableName(string $var): string
	{
		if (preg_match('/\{(\w+)\}/m', $var, $matches)) {
			return $matches[1];
		};
		
		return $var;
	}
	
	/**
	 * @param string                    $httpCode
	 * @param Reference|Schema|Response $schema
	 * @throws \Exception
	 * @return string
	 */
	private function makeResponseBody(string $httpCode, $schema): string
	{
		$ucHttpCode             = ucfirst($httpCode);
		$generator              = $this->getGenerator($schema, '../content/%className%' . $ucHttpCode, "responses/$httpCode");
		$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
		$generator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
		$generator->make();
		return $generator->getFullClassPath();
	}
	
	public function parseResponse(string $httpCode, $resource)
	{
		$contentType = Omg::getContentType($resource);
		if ($resource instanceof Reference and Omg::isComponentResponse($resource->getReference())) {
			
			$modelClass = Utils::ns($resource->getReference())->getFullClassPath(Omg::getComponentResponseContentNsPart());
			$this->parseResponse($httpCode, $resource->resolve());
			//debug(get_class($resource->resolve()));
			//debug(Utils::ns($resource->getReference())->getFullClassPath(Omg::getComponentResponseContentNsPart()));
		}
		elseif ($resource instanceof Reference) {
			addExtraErrorInfo('class', $resource->getReference());
			Omg::error('un implemented');
		}
		elseif ($resource instanceof Response) {
			$contentType = Omg::getContentType($resource);
			$content     = $resource->content[$contentType]->schema;
			//$className   = $this->makeResponseBody($httpCode, $resource);
			
			if ($content instanceof Reference) {
				$modelClass = Omg::getReferenceClassPath($content->getReference());
				$className   = $this->makeResponseBody($httpCode, $content->resolve());
				$this->tpl->registerHttpResponse($httpCode, $className, $modelClass, $contentType);
			}
			else {
				//debug($this->path, get_class($content));
				
				//debug(Utils::ns($content->getReference())->getFullClassPath(Omg::getComponentResponseContentNsPart()));
				return;
				$generator              = $this->getGenerator($content, Omg::getComponentResponseContentNsPart(), Omg::getComponentResponseContentNsPart());
				$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
				$generator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
				$generator->make();
				$this->setContentMethod($generator->getFullClassPath());
			}
		}
		else {
			debug(get_class($resource));
		}
		//$this->tpl->registerHttpResponse($httpCode, Omg::getReferenceClassPath($response->getReference()), Omg::getReferenceClassPath($response->getReference()), $contentType);
	}
	
}