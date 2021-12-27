<?php

namespace Infira\omg\templates;

use Nette\PhpGenerator\Method;
use Infira\omg\helper\Utils;

/**
 * @mixin Method
 */
class MethodTemplate extends Magics
{
	/**
	 * @var \Nette\PhpGenerator\Method
	 */
	protected $method;
	private   $lines           = [];
	private   $eqLineSetMaxLen = 0;
	
	public function __construct(Method $method)
	{
		$this->method = &$method;
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
	
	public function addBodyLines(array $lines)
	{
		array_walk($lines, function ($line)
		{
			$this->addBodyLine($line);
		});
	}
	
	public function addBodyLine(string $line)
	{
		$this->doAddBodyLine($line, 'normal');
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