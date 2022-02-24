<?php

namespace Infira\omg;

use Infira\Utils\File;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Parameter;
use Infira\omg\helper\Utils;
use Nette\PhpGenerator\PhpFile;
use Infira\omg\generator\{SchemaArrayGenerator, SchemaBlankGenerator, SchemaObjectGenerator};
use Infira\omg\helper\Ns;
use \Infira\omg\helper\ParametersSpec;

abstract class Generator
{
	private $makeFile;
	/**
	 * @var \Nette\PhpGenerator\PhpFile
	 */
	protected $phpf;
	
	/**
	 * @var \Infira\omg\templates\ClassTemplate
	 */
	public $tpl;
	
	/**
	 * @var \Nette\PhpGenerator\PhpNamespace
	 */
	protected $phpNamespace;
	
	
	/**
	 * @var \Nette\PhpGenerator\ClassType
	 */
	protected $ct;
	
	private static $generatedItems = [];
	
	/**
	 * @var \Infira\omg\Config
	 */
	public $config;
	
	protected $variables = [];
	/**
	 * @var \Infira\omg\helper\Ns
	 */
	public $ns;
	
	/**
	 * @var \Infira\omg\helper\Ns
	 */
	public $schemaLocation;
	
	public function __construct(string $namespace, string $schemaLocation, string $tplClass)
	{
		if ($schemaLocation[0] != '#' and $schemaLocation != 'register') {
			Omg::error('Schema location must start with #', ['$schemaLocation' => $schemaLocation]);
		}
		$this->ns = Utils::ns($namespace);
		$this->ns->set($namespace);
		if ($suffix = Omg::getClassnameSuffix($schemaLocation)) {
			if ($suffix == 'Response') {
				//Omg::debug($namespace);
			}
			$this->ns->setClassSuffix($suffix);
		}
		$this->schemaLocation = new Ns('#', '/');
		$this->schemaLocation->set($schemaLocation);
		
		
		$this->phpf         = new PhpFile();
		$this->phpNamespace = $this->phpf->addNamespace($this->ns->get('../'));
		$this->ct           = $this->phpNamespace->addClass(Utils::className($this->ns->getFullClassName()));
		$this->tpl          = new $tplClass($this->ct, $this->phpNamespace);
		$this->tpl->setGenerator($this);
		$this->tpl->addComment('Schema location ' . $this->schemaLocation->get());
	}
	
	
	public static function makeFile(string $file, string $src): string
	{
		$file = Config::$destination . "/$file";
		if (!is_dir(dirname($file))) {
			mkdir(dirname($file), 0777, true);
		}
		
		File::create($file, $src);
		
		return $file;
	}
	
	/**
	 * @throws \Exception
	 * @return Generator
	 */
	public function make()
	{
		$cid = $this->ns->get();
		if (!$cid) {
			Omg::error("Class name creaton failed ($cid)");
		}
		if (Omg::isGenerated($cid)) {
			Omg::error("class $cid is already generated from " . Omg::getGenerated($cid));
		}
		Omg::setGenerated($cid, $this->schemaLocation->get());
		$className = Utils::className($this->ns->getFullClassName());
		
		
		$file = str_replace('\\', DIRECTORY_SEPARATOR, str_replace(Config::getRootNamespace() . '\\', '', $this->ns->get("../") . "\\$className.php"));
		$this->tpl->finalize();
		
		$this->makeFile = self::makeFile($file, Utils::printNette($this->phpf));
		
		return $this;
	}
	
	//region namespace helpers
	public final function getClassName(): string
	{
		return $this->ns->getClassName();
	}
	
	public final function getFullClassPath(string ...$parts): string
	{
		return $this->ns->getFullClassPath(...$parts);
	}
	
	/**
	 * @param Reference|Schema|ParametersSpec $bodySchema
	 * @param string                          $namespace
	 * @param string                          $schemaLocation
	 * @param string|null                     $type if null it will be autodetect
	 * @throws \Exception
	 * @return SchemaArrayGenerator|SchemaBlankGenerator|SchemaObjectGenerator
	 */
	protected final function getGenerator($bodySchema, string $namespace, string $schemaLocation, string $type = null)
	{
		return Omg::getGenerator($bodySchema, $this->ns->get($namespace), $this->schemaLocation->get($schemaLocation), $type);
	}
	
	protected final function makeIfNeeded($from, string $namespace, string $schemaLocation, string $overRideType = null): \stdClass
	{
		$res            = new \stdClass();
		$res->dataClass = null;
		$res->type      = null;
		
		if ($from instanceof Reference) {
			if (Omg::isComponent($from) and Omg::isMakeable($from)) {
				$res->dataClass = Omg::getReferenceClassPath($from);
			}
			$res->type = $overRideType ?: Omg::getType($from);
		}
		
		elseif ($from instanceof Schema and $from->type == 'array' && $from->items instanceof Reference and Omg::isComponent($from->items) and !Omg::isMakeable($from->items) and 1 == 2) {
			$ref            = $from->items->getReference();
			$res->dataClass = $this->getGenerator($from, $namespace, $schemaLocation . '/$ref:' . $ref, 'array')->make()->getFullClassPath();
			$res->type      = 'array';
		}
		elseif ($from instanceof Schema and $from->type == 'array') {
			if ($from->items instanceof Reference) {
				$ref            = $from->items->getReference();
				$schemaLocation .= '/$ref:' . $ref;
			}
			$res->dataClass = $this->getGenerator($from, $namespace, $schemaLocation, 'array')->make()->getFullClassPath();
			$res->type      = 'array';
			//return $this->makeIfNeeded($from->items, $namespace, $schemaLocation, 'array');
		}
		elseif ($from instanceof Schema and Omg::isMakeable($from->type)) {
			$res->dataClass = $this->getGenerator($from, $namespace, $schemaLocation, $from->type)->make()->getFullClassPath();
			$res->type      = $from->type;
			$res->debug     = 1;
		}
		elseif ($from instanceof Schema and $from->type == 'array' && $from->items instanceof Reference && !Omg::isComponent($from->items)) {
			Omg::error('not implemented');
		}
		
		return $res;
	}
	
	//endregion
}