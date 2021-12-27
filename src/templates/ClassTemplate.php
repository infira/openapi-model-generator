<?php

namespace Infira\omg\templates;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PhpFile;
use Infira\omg\Generator;
use Infira\omg\helper\Utils;
use Infira\omg\Omg;
use Infira\omg\Config;

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
	
	public function createMethod(string $name)
	{
		$method          = $this->class->addMethod($name);
		$method          = new MethodTemplate($method);
		$this->methods[] = &$method;
		
		return $method;
	}
	
	public function addImports(array $imports)
	{
		array_walk($imports, [$this, 'import']);
	}
	
	public function import(string $name, ?string $alias = null)
	{
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
	
	public function addMethod(string $name, ?string $phpType, ?string $dataClass, string $argName, bool $nullable, string $returnType, ?string $description, array $bodyLines)
	{
		$dataClassName = $dataClass;
		if ($dataClass) {
			$this->import($dataClass);
			$dataClassName = Utils::extractName($dataClass);
		}
		
		$method = $this->createMethod($name);
		$method->setReturnType($returnType);
		
		if ($description) {
			$method->addComment($description);
		}
		$param = $method->addParameter($argName);
		
		$method->addBodyLines($bodyLines);
		if ($argName) {
			if (Config::$phpVersion >= 7.4) {
				$param->setType(Utils::makeDocType($phpType, $dataClass, $nullable));
			}
			else {
				$param->setType(Utils::makeParameterType($phpType, $dataClassName, $nullable));
				$docArgumentType = Utils::makeDocType($phpType, $dataClassName, $nullable);
				$method->addComment(sprintf('@param %s $%s', $docArgumentType, $argName));
				$method->addComment('');
				$method->addComment('@return ' . $returnType);
			}
		}
	}
	
	public function addDocProperty(string $name, string $phpType, ?string $dataClass, bool $nullable, ?string $description)
	{
		$docType = Utils::makeDocType($phpType, $dataClass, $nullable);
		$this->addComment('@property ' . $docType . ' $' . $name . ' ' . $description);
	}
}