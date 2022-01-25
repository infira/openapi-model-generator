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
		parent::__construct(Utils::ns(...$ns)->get(), "#/paths$path/$method", \Infira\omg\templates\PathOperation::class);
		//debug([$this->path => [Config::$pathNamespaceTemplate, $this->getFullClassPath()]]);
		//debug('----------------------------------- THE END ----------------------------------------------------------------');
		
		
		$aOperationID = $operation->operationId ? "'$operation->operationId'" : 'null';
		$aPath        = $path ? "'$path'" : 'null';
		$this->tpl->addConstructorLine('parent::__construct(\'%s\', %s, %s);', $method, $aPath, $aOperationID);
		
		$this->tpl->addComment('Operation path %s %s', strtoupper($method), $path);
		$this->tpl->addComment('Operation ID %s ', $this->operation->operationId);
		
		$operationClass = Config::getOperationClass() ?: Omg::getOperationPath();
		if ($operationClass === null) {
			$this->tpl->import(Omg::getOperationPath(), 'Operation');
		}
		$this->tpl->setExtends($operationClass);
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
				$description  = $this->operation->requestBody->resolve()->descrtipion ?? '';
				$requestClass = Omg::getReferenceClassPath($this->operation->requestBody->getReference());
			}
			else {
				$generateFrom = $this->operation->requestBody->content[Omg::getContentType($this->operation->requestBody)]->schema;
				$description  = $this->operation->requestBody->description;
				
				$generator = $this->getGenerator($generateFrom, '../body/%className%Body', "requestBodies");
				$generator->tpl->addConstructorLine('$this->fillNonExistingWithDefaultValues = true;');
				$generator->make();
				$requestClass = $generator->getFullClassPath();
			}
			$requestClassAlias = 'RequestInput';
			
			$this->tpl->import($requestClass, $requestClassAlias);
			$this->tpl->addConstructorLine('$this->%s = new %s;', Config::$operationInputParameterName, $requestClassAlias);
			$prop = $this->tpl->addPropertyType(Config::$operationInputParameterName, $requestClass);
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
	 * @throws \cebe\openapi\exceptions\UnresolvableReferenceException
	 * @return \Infira\omg\generator\Response
	 */
	private function makeResponse(string $httpCode, $resource): \Infira\omg\generator\Response
	{
		$statusName = Utils::className(Config::getHttpStatusName($httpCode));
		$generator  = new \Infira\omg\generator\Response($this->ns->get('../responses/%className%' . $statusName . 'Response'), $this->schemaLocation->get("$httpCode/response"));
		if ($resource instanceof Reference) {
			//debug($resource->getReference());
			Omg::notImplementedYet();
			$generator->tpl->setExtends(Omg::getReferenceClassPath($resource->getReference()));
		}
		else {
			//debug(get_class($resource));
		}
		//$generator->tpl->setExtends()
		$generator->beforeMake($resource);
		$generator->make();
		
		return $generator;
	}
	
	private function extractPathVariableName(string $var): string
	{
		if (preg_match('/\{(\w+)\}/m', $var, $matches)) {
			return $matches[1];
		};
		
		return $var;
	}
	
	public function parseResponse(string $httpCode, $resource, string $parentResponseClass = null)
	{
		$contentType = Omg::getContentType($resource);
		if ($resource instanceof Reference and Omg::isComponentResponse($resource->getReference())) {
			$responseClass = Omg::getReferenceClassPath($resource->getReference());
			$this->parseResponse($httpCode, $resource->resolve(), $responseClass);
		}
		elseif ($resource instanceof Reference) {
			addExtraErrorInfo('class', $resource->getReference());
			Omg::notImplementedYet();
		}
		elseif ($resource instanceof Response and $parentResponseClass) {
			$contentType = Omg::getContentType($resource);
			$content     = $resource->content[$contentType]->schema;
			if ($content instanceof Reference) {
				$modelClass = Omg::getReferenceClassPath($content->getReference());
				$this->tpl->registerHttpResponse($httpCode, $parentResponseClass, $modelClass);
			}
			else {
				$this->tpl->registerHttpResponse($httpCode, $parentResponseClass, Utils::ns($parentResponseClass)->getFullClassPath(Omg::getComponentResponseContentNsPart()));
			}
		}
		elseif ($resource instanceof Response and !$parentResponseClass) {
			$response = $this->makeResponse($httpCode, $resource);
			$this->tpl->registerHttpResponse($httpCode, $response->getFullClassPath(), $response->getContentClass());
		}
		else {
			Omg::notImplementedYet();
		}
	}
	
}