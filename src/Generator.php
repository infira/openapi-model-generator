<?php

namespace Infira\omg;

use Infira\Utils\File;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Response;
use Infira\omg\helper\Utils;
use Nette\PhpGenerator\PhpFile;
use Infira\omg\generator\{SchemaArrayModel, SchemaBlankModel, SchemaObjectModel};

abstract class Generator
{
	public $namespace      = [];
	public $schemaLocation = [];
	
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
	protected $ns;
	
	
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
	
	public function __construct(string $namespace, string $schemaLocation, string $tplClass)
	{
		$this->setNamespace($namespace);
		$this->schemaLocation = explode('\\', $this->getSchemaLocation($schemaLocation));
		
		
		$this->phpf = new PhpFile();
		$this->ns   = $this->phpf->addNamespace($this->getNamespace('../'));
		$this->ct   = $this->ns->addClass(Utils::className($this->getClassName()));
		$this->tpl  = new $tplClass($this->ct, $this->ns);
		$this->tpl->setGenerator($this);
		$this->tpl->addComment('Schema location ' . $this->getSchemaLocation());
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
	
	protected final function makeClass(): string
	{
		$cid = $this->getNamespace();
		if (!$cid) {
			Omg::error("Class name creaton failed ($cid)");
		}
		if (Omg::isGenerated($cid)) {
			Omg::error("class $cid is already generated from " . Omg::getGenerated($cid));
		}
		Omg::setGenerated($cid, $this->getSchemaLocation());
		$className = Utils::className($this->getClassName());
		
		
		$file = str_replace('\\', DIRECTORY_SEPARATOR, str_replace(Config::getRootNamespace() . '\\', '', $this->getNamespace("../") . "\\$className.php"));
		$this->tpl->finalize();
		
		return self::makeFile($file, $this->phpf->__toString());
	}
	
	/**
	 * @param Reference|Response $resource
	 * @return string
	 */
	protected final function getContentType($resource): string
	{
		if ($resource instanceof Reference) {
			return $this->getContentType($resource->resolve());
		}
		else//if ($resource instanceof Response)
		{
			return array_keys((array)$resource->content)[0];
		}
	}
	
	protected final function getReferenceClassPath(string $ref): string
	{
		if (Omg::isComponentResponse($ref)) {
			$type = 'response';
		}
		elseif (Omg::isComponentSchema($ref)) {
			$type = 'schema';
		}
		elseif (Omg::isComponentRequestBody($ref)) {
			$type = 'requestBodies';
		}
		else {
			Omg::error('unknown reference');
		}
		
		return '\\' . $this->getNamespace('/component', $type, ucfirst(Utils::extractName($ref)));
	}
	
	private final function constructPath(string $rootPath, array $currentPath, string ...$parts): string
	{
		if (!$parts) {
			$parts = [''];
		}
		array_walk($parts, function (&$p) { $p = str_replace('../', '[_CD_]', $p); });
		$parts[0]  = trim($parts[0]);
		$firstPart = strlen($parts[0]) > 0 ? $parts[0] : '';
		$className = $this->getClassName();
		
		if (substr($firstPart, 0, strlen($rootPath)) == $rootPath) {
			$parts[0]  = '/' . substr($firstPart, strlen($rootPath) + 1);
			$firstPart = '/';
		}
		
		if (substr($firstPart, 0, 1) == '/') //$ROOT_NAMESPACE/....$parts
		{
			$root     = [$rootPath];
			$parts[0] = substr($parts[0], 1);
		}
		elseif (substr($firstPart, 0, 2) == './') // /....$parts
		{
			$root     = [];
			$parts[0] = substr($parts[0], 2);
		}
		elseif (substr($firstPart, 0, 2) == '..') //$CURRENT_NAMESPACE/....$parts - (replace classname)
		{
			$parts[0] = substr($parts[0], 2);
			$root     = array_slice($currentPath, 0, -1);
		}
		elseif (substr($firstPart, 0, 1) == '.') //$CURRENT_NAMESPACE/$name....$parts - (extend classname)
		{
			$parts[0] = substr($parts[0], 1);
			$root     = array_slice($currentPath, 0, -1);
			$parts    = [$className . join('', $parts)];
		}
		else {
			$root = $currentPath ?: [$rootPath];
		}
		
		$fullParts = array_merge($root, $parts);
		foreach ($fullParts as $key => $item) {
			if (!$item) {
				unset($fullParts[$key]);
				continue;
			}
			$item            = str_replace('/', '\\', $item);
			$item            = str_replace('\\\\\\', '\\', $item);
			$item            = str_replace('\\\\', '\\', $item);
			$fullParts[$key] = $item;
		}
		$final = join('\\', $fullParts);
		$final = $this->cdNs($final);
		
		if (strpos($final, '.') !== false) {
			Omg::error('not allowed character in namespace:' . $final);
		}
		
		return str_replace('%className%', $className, $final);;
	}
	
	private final function cdNs(string $ns): string
	{
		$cdPos   = strpos($ns, '[_CD_]');
		$afterCd = substr($ns, $cdPos + 6);
		if ($cdPos !== false) {
			if ($afterCd) {
				if ($afterCd[0] != '\\') {
					$afterCd = '\\' . $afterCd;
				}
			}
			
			$firstCdPos = $cdPos;
			$prevPos    = $cdPos - 1;
			if ($ns[$prevPos] == '\\') {
				$firstCdPos--;
			}
			$tmpNs = substr($ns, 0, $firstCdPos);
			
			return $this->cdNs(substr($ns, 0, strrpos($tmpNs, '\\')) . $afterCd);
		}
		
		return $ns;
	}
	
	//region namespace helpers
	
	private function setNamespace(string ...$ns)
	{
		$this->namespace = explode('\\', $this->getNamespace(...$ns));
	}
	
	/**
	 * @param string ...$parts
	 *                        NB! namespace path separated with / where last item will be always as class name
	 *                        also $parts last path item will be used as classname
	 *                        path usually contains $ROOT_NAMESPACE/...$parts/$className
	 *                        $ROOT_NAMESPACE what is set in config.json
	 *                        $CURRENT_NAMESPACE = $ROOT_NAMESPACE/...$parts/className
	 *                        $CURRENT_NAMESPACE_NO_CN = $ROOT_NAMESPACE/...$parts
	 *
	 *                        %className% - will replace with current classname
	 *                        using ../ in parts will work just like realpath('path1/path2/../') end as path1/
	 *                        if $parts first item starts with /   then will give result $ROOT_NAMESPACE/....$parts
	 *                        if $parts first item starts with ./  then will give result /....$parts
	 *                        if $parts first item starts with ..  then will give result $CURRENT_NAMESPACE/....$parts - (replace classname)
	 *                        if $parts first item starts with .   then will give result $CURRENT_NAMESPACE/$className....$parts - (extend classname)
	 *                        else $ROOT_NAMESPACE/...$parts
	 * @return string
	 */
	public final function getNamespace(string ...$parts): string
	{
		return $this->constructPath(Config::getRootNamespace(), $this->namespace, ...$parts);
	}
	
	/**
	 * @param string ...$parts
	 *                        NB! namespace path separated with / where last item will be always as class name
	 *                        also $parts last path item will be used as classname
	 *                        path usually contains $ROOT_NAMESPACE/...$parts/$className
	 *                        $ROOT_NAMESPACE what is set in config.json
	 *                        $CURRENT_NAMESPACE = $ROOT_NAMESPACE/...$parts/className
	 *                        $CURRENT_NAMESPACE_NO_CN = $ROOT_NAMESPACE/...$parts
	 *
	 *                        %className% - will replace with current classname
	 *                        using ../ in parts will work just like realpath('path1/path2/../') end as path1/
	 *                        if $parts first item starts with /   then will give result $ROOT_NAMESPACE/....$parts
	 *                        if $parts first item starts with ./  then will give result /....$parts
	 *                        if $parts first item starts with ..  then will give result $CURRENT_NAMESPACE/....$parts - (replace classname)
	 *                        if $parts first item starts with .   then will give result $CURRENT_NAMESPACE/$className....$parts - (extend classname)
	 *                        else $ROOT_NAMESPACE/...$parts
	 * @return string
	 */
	public final function getSchemaLocation(string ...$parts): string
	{
		return str_replace('\\', '/', $this->constructPath('#', $this->schemaLocation, ...$parts));
	}
	
	protected final function getClassName(): string
	{
		return end($this->namespace);
	}
	
	protected final function getFullClassPath(string ...$parts): string
	{
		$ar        = explode('\\', $this->getNamespace(...$parts));
		$className = ucfirst($ar[array_key_last($ar)]);
		
		return '\\' . join('\\', array_merge(array_slice($ar, 0, -1), [$className]));
	}
	
	/**
	 * @param Reference|Schema $bodySchema
	 * @param string           $namespace
	 * @param string           $schemaLocation
	 * @param string|null      $type
	 * @throws \Exception
	 * @return SchemaArrayModel|SchemaBlankModel|SchemaObjectModel
	 */
	protected final function getGenerator($bodySchema, string $namespace, string $schemaLocation, string $type = null)
	{
		if ($bodySchema and !($bodySchema instanceof Reference) and !($bodySchema instanceof Schema)) {
			Omg::error('$bodySchema must be Reference or Schema ' . get_class($bodySchema) . ' was given');
		}
		if ($type and !in_array($type, ['object', 'array', 'auto'], true)) {
			Omg::error("unknown type('$type')");
		}
		if ($bodySchema instanceof Reference) {
			if (!Omg::isComponentRef($bodySchema->getReference())) {
				Omg::notImplementedYet();
			}
			$generator = Omg::getGenerator($type, $this->getNamespace($namespace), $this->getSchemaLocation($schemaLocation));
			$generator->tpl->setExtends($this->getReferenceClassPath($bodySchema->getReference()));
		}
		else {
			if ($bodySchema) {
				Omg::validateSchema($bodySchema);
				if ($type == 'auto') {
					$type = $bodySchema->type;
				}
				elseif ($type == null) {
					Omg::error('type undefined');
				}
			}
			$generator = Omg::getGenerator($type, $this->getNamespace($namespace), $schemaLocation, $bodySchema);
		}
		
		return $generator;
	}
	
	//endregion
}