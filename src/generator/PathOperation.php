<?php

namespace Infira\omg\generator;

use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Response;
use Infira\omg\Config;
use Infira\omg\Omg;
use cebe\openapi\spec\Schema;

class PathOperation extends ObjectTemplate
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
		parent::__construct('/path', "/$path/$method");
		$this->setVariable('httpResponses', []);
		$this->operation = $operation;
		$this->method    = $method;
		$this->path      = $path;
		
		
		$pathParts     = explode('/', substr($path, 1));
		$templateParts = explode('/', Config::$pathNamespaceTemplate);
		
		$ns = [];
		foreach ($templateParts as $part)
		{
			$ns = array_merge($ns, $this->parseTemplatePart($part));
		}
		$this->setNamespace(...$ns);
		//debug([$this->path => [Config::$pathNamespaceTemplate, $this->getFullClassPath()]]);
		//debug('----------------------------------- THE END ----------------------------------------------------------------');
		
		
		$aOperationID = $operation->operationId ? "'$operation->operationId'" : 'null';
		$aPath        = $path ? "'$path'" : 'null';
		$this->addConstructorLine('parent::__construct(\'%s\', %s, %s);', $method, $aPath, $aOperationID);
		
		$this->addDocDescriptionLine('Operation path %s %s', strtoupper($method), $path);
		$this->setExtender($this->getLibClassPath('Operation'));
		
	}
	
	private function parseTemplatePart(string $part)
	{
		$output = [];
		//debug(['$part' => $part]);
		if (preg_match_all('/\{(.+?)\}/m', $part, $varContentMatches))
		{
			//			debug(['$varContentMatches' => $varContentMatches[1]]);
			foreach ($varContentMatches[1] as $varMatch)
			{
				$output = array_merge($output, $this->parseTemplatePart($varMatch));
			}
		}
		elseif (strpos($part, '?') !== false)
		{
			$q = explode('?', $part);
			//			debug(['?' => [$part => $q]]);
			foreach ($q as $qk => $qv)
			{
				$q[$qk] = $this->parseTemplatePart($qv);
			}
			if (empty($q[0]))
			{
				$output = array_merge($output, $q[1]);
			}
			else
			{
				$output = array_merge($output, $q[0]);
			}
		}
		elseif (preg_match('/(\w+)\((.+)\)/m', $part, $functionMatches))
		{
			$function = $functionMatches[1];
			if (!in_array($function, ['generateName']))
			{
				Omg::error("Unknown template function('$function')");
			}
			$output = $this->parseTemplatePart($functionMatches[2]);
			//			debug(['$functionMatches' => ['match' => $functionMatches, 'parts' => $output]]);
			if ($function == 'generateName')
			{
				foreach ($output as $ok => $p)
				{
					$output[$ok] = ucfirst($p);
				}
				
				return [join('', $output)];
			}
		}
		elseif (preg_match('/(\w+)?(\[.*\])/m', $part, $varMatches))
		{
			//			debug(['$varMatches' => $varMatches]);
			$varName = $varMatches[1];
			$output  = $this->getVariableParts($varName);
			if (preg_match_all('/\[(.+?)\]/m', $varMatches[2], $positionMatches))
			{
				//debug(['$positionMatch' => $positionMatches[1]]);
				foreach ($positionMatches[1] as $positionMatch)
				{
					$positions = explode(':', $positionMatch);
					if ($positions[0] == 'last')
					{
						$positions[0] = array_key_last($output);
					}
					elseif ($positions[0] <= 0)
					{
						$positions[0] = 0;
					}
					
					if (count($positions) == 1)
					{
						$output = [$output[$positions[0]]];
					}
					else
					{
						$from = $positions[0];
						$to   = $positions[1];
						if ($to == '*')
						{
							$to = 999;
						}
						
						$output = array_slice($output, $from, $to);
					}
					
					if (!$output)
					{
						break;
					}
				}
			}
		}
		else
		{
			$output = $this->getVariableParts($part);
			//			debug(['else' => $part]);
		}
		
		return $output;
	}
	
	private function getVariableParts(string $variable): array
	{
		switch ($variable)
		{
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
		if (empty($value))
		{
			return [];
		}
		
		if ($value[0] == '/')
		{
			$value = substr($value, 1);
		}
		
		$parts = explode('/', $value);
		if ($variable == 'path')
		{
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
			if (in_array($part, ['__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'match', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield', 'yield', 'from']))
			{
				$part = "_$part";
			}
		});
		
		return $parts;
	}
	
	public function make(): string
	{
		//parse requests
		if ($this->operation->requestBody)
		{
			if ($this->operation->requestBody instanceof Reference)
			{
				$generateFrom = $this->operation->requestBody;
				$description  = $this->operation->requestBody->resolve()->descrtipion ?? '';
			}
			else
			{
				$generateFrom = $this->operation->requestBody->content['application/json']->schema;
				$description  = $this->operation->requestBody->description;
			}
			$generator = $this->getGenerator($generateFrom, '../body/%className%Body', "requestBodies", 'auto');
			$generator->addConstructorLine('$this->fillNonExistingWithDefaultValues = true;');
			$generator->make();
			$this->addConstructorLine('$this->registerRequestBody(\'%s\');', $generator->getFullClassPath());
			$this->addDocProperty('rb', 'singleClass', $generator->getFullClassPath(), false, $description);
			
		}
		
		//##################### Responses
		/** @var Response $response */
		foreach ($this->operation->responses as $httpCode => $response)
		{
			if ($response instanceof Reference)
			{
				$this->makeResponse($response, $httpCode);
				
			}
			elseif ($response instanceof Response)
			{
				$this->makeResponse($response->content['application/json']->schema, $httpCode);
			}
			else
			{
				Omg::notImplementedYet();
			}
		}
		
		$this->addDocMethod('getResponse', null, 'singleClass', join('|', $this->responseClasses), false, 'get active response');
		
		return $this->makeClass();
	}
	
	/**
	 * @param Reference|Schema $bodySchema
	 * @param string           $httpCode
	 */
	private function makeResponse($bodySchema, string $httpCode)
	{
		$ucHttpCode = ucfirst($httpCode);
		if ($bodySchema instanceof Reference and Omg::isComponentRef($bodySchema->getReference()))
		{
			$this->registerHttpResponse($httpCode, $this->getReferenceClassPath($bodySchema->getReference()));
		}
		else
		{
			$generator              = $this->getGenerator($bodySchema, '../content/%className%' . $ucHttpCode, "responses/$httpCode", 'auto');
			$propertiesAreMandatory = Config::$mandatoryResponseProperties ? 'true' : 'false';
			$generator->addConstructorLine('$this->propertiesAreMandatory = ' . $propertiesAreMandatory . ';');
			
			$generator->make();
			$this->registerHttpResponse($httpCode, $generator->getFullClassPath());
		}
	}
	
	private function registerHttpResponse(string $httpCode, string $class)
	{
		$this->addConstructorLine('$this->registerResponse(\'%s\',\'%s\');', $httpCode, $class);
		$this->addDocProperty("res$httpCode", 'singleClass', $class, false, "http code $httpCode class");
		$this->add2Variable('httpResponses', ['code' => $httpCode, 'class' => $class]);
		$this->responseClasses[] = $class;
		
		$bodyLines = [
			'$this->activeResponseHttpCode = \'' . $httpCode . '\';',
			sprintf('$this->%s = $content;', "res$httpCode"),
			'return $this;',
		];
		$this->addMethod("set$httpCode", $class, $class, 'content', false, 'self', "http operation(httpCode=$httpCode) data model", $bodyLines);
	}
	
	private function isPathVariable(string $part)
	{
		return preg_match('/\{(\w+)\}/m', $part);
	}
	
	private function extractPathVariableName(string $var): string
	{
		if (preg_match('/\{(\w+)\}/m', $var, $matches))
		{
			return $matches[1];
		};
		
		return $var;
	}
}