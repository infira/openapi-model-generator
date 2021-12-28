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
		parent::__construct($this->getNamespace(...$ns), "/$path/$method", \Infira\omg\templates\PathOperation::class);
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
			if (!in_array($function, ['generateName'])) {
				Omg::error("Unknown template function('$function')");
			}
			$output = $this->parseTemplatePart($functionMatches[2]);
			//			debug(['$functionMatches' => ['match' => $functionMatches, 'parts' => $output]]);
			if ($function == 'generateName') {
				foreach ($output as $ok => $p) {
					$output[$ok] = ucfirst($p);
				}
				
				return [join('', $output)];
			}
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
	
	public function make(): string
	{
		//parse requests
		if ($this->operation->requestBody) {
			if ($this->operation->requestBody instanceof Reference) {
				$generateFrom = $this->operation->requestBody;
				$description  = $this->operation->requestBody->resolve()->descrtipion ?? '';
			}
			else {
				$generateFrom = $this->operation->requestBody->content[$this->getContentType($this->operation->requestBody)]->schema;
				$description  = $this->operation->requestBody->description;
			}
			$generator = $this->getGenerator($generateFrom, '../body/%className%Body', "requestBodies", 'auto');
			$generator->tpl->addConstructorLine('$this->fillNonExistingWithDefaultValues = true;');
			$generator->make();
			
			$inputClass     = $generator->getFullClassPath();
			$inputClassName = Utils::extractName($inputClass);
			
			$this->tpl->import($inputClass);
			$this->tpl->addConstructorLine('$this->%s = new %s;', Config::$operationInputParameterName, $inputClassName);
			$this->tpl->addDocPropertyComment(Config::$operationInputParameterName, $inputClassName, $description);
			
		}
		
		//##################### Responses
		/** @var Response $response */
		foreach ($this->operation->responses as $httpCode => $response) {
			$contentType = $this->getContentType($response);
			if ($response instanceof Reference) {
				$this->makeResponse($response, $httpCode, $contentType);
			}
			elseif ($response instanceof Response) {
				$this->makeResponse($response->content[$contentType]->schema, $httpCode, $contentType);
			}
			else {
				Omg::notImplementedYet();
			}
		}
		
		return $this->makeClass();
	}
	
	/**
	 * @param Reference|Schema $bodySchema
	 * @param string           $httpCode
	 * @param string           $contentType
	 */
	private function makeResponse($bodySchema, string $httpCode, string $contentType)
	{
		$ucHttpCode = ucfirst($httpCode);
		if ($bodySchema instanceof Reference and Omg::isComponentRef($bodySchema->getReference())) {
			$this->tpl->registerHttpResponse($httpCode, $this->getReferenceClassPath($bodySchema->getReference()), $contentType);
		}
		else {
			$generator              = $this->getGenerator($bodySchema, '../content/%className%' . $ucHttpCode, "responses/$httpCode", 'auto');
			$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
			$generator->tpl->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
			
			$generator->make();
			$this->tpl->registerHttpResponse($httpCode, $generator->getFullClassPath(), $contentType);
		}
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
}