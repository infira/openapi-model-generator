<?php

namespace Infira\omg;

use Infira\Utils\File;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Infira\omg\helper\Utils;
use Nette\PhpGenerator\PhpFile;
use Infira\omg\generator\{SchemaArrayGenerator, SchemaBlankGenerator, SchemaObjectGenerator};
use Infira\omg\helper\Ns;

abstract class Generator
{
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
		$this->ns = Utils::ns($namespace);
		$this->ns->set($namespace);
		$this->schemaLocation = new Ns('#', '/');
		$this->schemaLocation->set($schemaLocation);
		
		
		$this->phpf         = new PhpFile();
		$this->phpNamespace = $this->phpf->addNamespace($this->ns->get('../'));
		$this->ct           = $this->phpNamespace->addClass(Utils::className($this->getClassName()));
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
	
	public function make(): string
	{
		$cid = $this->ns->get();
		if (!$cid) {
			Omg::error("Class name creaton failed ($cid)");
		}
		if (Omg::isGenerated($cid)) {
			Omg::error("class $cid is already generated from " . Omg::getGenerated($cid));
		}
		Omg::setGenerated($cid, $this->schemaLocation->get());
		$className = Utils::className($this->getClassName());
		
		
		$file = str_replace('\\', DIRECTORY_SEPARATOR, str_replace(Config::getRootNamespace() . '\\', '', $this->ns->get("../") . "\\$className.php"));
		$this->tpl->finalize();
		
		return self::makeFile($file, Utils::printNette($this->phpf));
	}
	
	//region namespace helpers
	protected final function getClassName(): string
	{
		return $this->ns->getClassName();
	}
	
	protected final function getFullClassPath(string ...$parts): string
	{
		return $this->ns->getFullClassPath(...$parts);
	}
	
	/**
	 * @param Reference|Schema $bodySchema
	 * @param string           $namespace
	 * @param string           $schemaLocation
	 * @param string|null      $type if null it will be autodetect
	 * @throws \Exception
	 * @return SchemaArrayGenerator|SchemaBlankGenerator|SchemaObjectGenerator
	 */
	protected final function getGenerator($bodySchema, string $namespace, string $schemaLocation, string $type = null)
	{
		return Omg::getGenerator($bodySchema, $this->ns->get($namespace), $this->schemaLocation->get($schemaLocation), $type);
	}
	
	//endregion
}