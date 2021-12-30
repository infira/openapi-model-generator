<?php

namespace Infira\omg\templates;

use Nette\PhpGenerator\Method;
use Infira\omg\helper\Utils;
use Infira\omg\Config;
use Nette\PhpGenerator\Parameter;

/**
 * @mixin Method
 */
class MethodTemplate extends Magics
{
	/**
	 * @var \Nette\PhpGenerator\Method
	 */
	protected $method;
	
	/**
	 * @var \Infira\omg\templates\ClassTemplate
	 */
	private $ct;
	
	private $lines           = [];
	private $eqLineSetMaxLen = 0;
	
	public function __construct(Method $method, ClassTemplate $ct)
	{
		$this->method = &$method;
		$this->ct     = &$ct;
		$this->setMagicVar('method');
	}
	
	public function addEqBodyLine(string $set, $value, $valueFormat = null)
	{
		$this->eqLineSetMaxLen = max($this->eqLineSetMaxLen, strlen($set));
		if (!$valueFormat) {
			$parsed      = Utils::parseValueFormat($value, $valueFormat);
			$value       = $parsed[1];
			$valueFormat = $parsed[0];
		}
		$this->doAddBodyLine([$set => sprintf($valueFormat, $value)], 'eq');
	}
	
	public function addBodyLine(string ...$lines)
	{
		foreach ($lines as $line) {
			$this->doAddBodyLine($line, 'normal');
		}
	}
	
	public function setReturnType(string $type, $addComment = true): MethodTemplate
	{
		$this->method->setReturnType($type);
		if ($addComment) {
			$this->addComment('@return %s', Utils::extractName($type));
		}
		
		return $this;
	}
	
	public function addComment(?string $format, ...$values)
	{
		if (!$format) {
			return;
		}
		$this->method->addComment(sprintf($format, ...$values));
	}
	
	public function addParamComment(string $name, string $type)
	{
		$this->addComment('@param %s $%s', Utils::toPhpType($type), $name);
	}
	
	public function addTypeParameter(string $paramName, string $paramType, bool $addComment = true): Parameter
	{
		if (Utils::isClassLike($paramType)) {
			return $this->addClassParameter($paramName, $paramType);
		}
		$paramType = Utils::toPhpType($paramType);
		$param     = $this->method->addParameter($paramName);
		$param->setType(Utils::toPhpType($paramType));
		if ($addComment) {
			$this->addParamComment($paramName, $paramType);
		}
		
		return $param;
	}
	
	public function addClassParameter(string $paramName, string $classType = null): Parameter
	{
		$types[] = 'array';
		$types[] = '\stdClass';
		$types[] = 'callable';
		$types[] = 'string';
		$param   = $this->method->addParameter($paramName, Utils::literal('Storage::NOT_SET'));
		if (Config::$phpVersion > 7.3) {
			$param->setType('mixed');
		}
		if ($classType) {
			$types[] = $classType;
		}
		$this->addComment('@param %s $%s', join('|', $types), $paramName);
		
		return $param;
	}
	
	private function doAddBodyLine($line, string $type)
	{
		$this->lines[] = ['line' => $line, 'type' => $type];
	}
	
	
	public function construct(): Method
	{
		foreach ($this->lines as $line) {
			$lineStr = $line['line'];
			if ($line['type'] == 'eq') {
				$set     = array_key_first($lineStr);
				$value   = $lineStr[$set];
				$eq      = str_repeat(' ', $this->eqLineSetMaxLen - strlen($set)) . ' = ';
				$lineStr = $set . $eq . $value;
			}
			$lineStr = trim($lineStr);
			if (substr($lineStr, -1) != ';') {
				$lineStr .= ';';
			}
			$this->addBody($lineStr);
		}
		
		return $this->method;
	}
}