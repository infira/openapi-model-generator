<?php

namespace Infira\omg\templates;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PhpFile;
use Infira\omg\Generator;
use Infira\omg\helper\Utils;
use Infira\omg\Omg;
use Infira\omg\Config;
use Nette\PhpGenerator\Property;

/**
 * @mixin ClassType
 */
class ClassTemplate extends Magics
{
	/**
	 * @var ClassType
	 */
	protected $class;
	
	/**
	 * @var \Infira\omg\templates\MethodTemplate[]
	 */
	private $methods = [];
	
	/**
	 * @var PhpNamespace|null
	 */
	private $ns = null;
	
	/**
	 * @var PhpFile|PhpNamespace
	 */
	protected $phpf = null;
	
	/**
	 * @var \Infira\omg\Generator
	 */
	protected $generator;
	
	public function __construct(ClassType $class, object $phpNamespace)
	{
		$this->class = &$class;
		$this->phpf  = &$phpNamespace;
		$this->setMagicVar('class');
	}
	
	public function setGenerator(Generator &$generator)
	{
		$this->generator = $generator;
	}
	
	public function finalize()
	{
		$this->beforeFinalize();
		array_walk($this->methods, function (&$method)
		{
			$method = $method->construct();
		});
		$this->class->setMethods($this->methods);
		$this->addComment('');
		$this->addComment('@author https://github.com/infira/openapi-model-generator/tree/v2');
	}
	
	public function createMethod(string $name, ?string $description = null)
	{
		$method = $this->class->addMethod($name);
		$method = new MethodTemplate($method, $this);
		if ($description) {
			$method->addComment($description);
		}
		$this->methods[] = &$method;
		
		return $method;
	}
	
	public function import(string $name, ?string $alias = null)
	{
		if ($name[0] == '?') {
			$name = substr($name, 1);
		}
		$this->phpf->addUse($name, $alias);
	}
	
	public function extendLib(string $libName)
	{
		$path = Omg::getLibPath($libName);
		$this->import($path, $libName);
		$this->setExtends($path);
	}
	
	public function addComment(string $format, string ...$values)
	{
		$this->class->addComment(vsprintf($format, $values));
	}
	
	public function addPropertyType(string $name, string $type): Property
	{
		$prop     = $this->class->addProperty($name);
		$nullable = $type[0] == '?';
		
		if ($type[0] == '?') {
			$type = substr($type, 1);
			if (Config::$phpVersion <= 7.3) {
				$type = "null|$type";
				$prop->setValue(null);
			}
		}
		if (Config::$phpVersion > 7.3) {
			
			$prop->setType($type);
			if ($nullable) {
				$prop->setNullable(true);
			}
		}
		else {
			$prop->addComment('@var ' . $type);
		}
		
		return $prop;
	}
	
	public function addDocPropertyComment(string $name, string $docType, ?string $description)
	{
		$docType     = join('|', Utils::makePhpTypes($docType, true));
		$description = $description ?? '';
		$this->addComment('@property %s $%s %s', $docType, $name, $description);
	}
}