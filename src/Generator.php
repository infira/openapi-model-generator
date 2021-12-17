<?php

namespace Infira\omg;

use Infira\Utils\Globals;
use Infira\Utils\File;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Infira\omg\helper\Tpl;

abstract class Generator
{
	public         $namespace      = [];
	public         $schemaLocation = [];
	private        $templateFile   = '';
	private static $generatedItems = [];
	
	/**
	 * @var \Infira\omg\Config
	 */
	public $config;
	
	protected $variables         = [];
	protected $requiredVariables = ['template'];
	
	public function __construct(string $namespace = '/', string $schemaLocation)
	{
		$this->setVariable('docProperties', []);
		$this->setVariable('methods', []);
		$this->setVariable('docDescriptionLines', []);
		$this->setVariable('constructorLines', []);
		$this->setVariable('variableLines', []);
		$this->setVariable('constructorArguments', '');
		$this->setNamespace($namespace);
		$this->schemaLocation = explode('\\', $this->getSchemaLocation($schemaLocation));
		$this->setExtender('');
	}
	
	
	public static function makeFile(string $file, string $src): string
	{
		$file = Config::$destination . "/$file";
		if (!is_dir(dirname($file)))
		{
			mkdir(dirname($file), 0777, true);
		}
		
		File::create($file, $src);
		
		return $file;
	}
	
	protected final function makeClass(): string
	{
		foreach ($this->requiredVariables as $rv)
		{
			if (!array_key_exists($rv, $this->variables))
			{
				Omg::error("required template variable '$rv' is missing");
			}
			elseif (!$this->variables[$rv])
			{
				Omg::error("required template variable '$rv' is empty");
			}
		}
		$vars = $this->variables;
		
		$cid = $this->getNamespace();
		if (!$cid)
		{
			Omg::error("Class name creaton failed ($cid)");
		}
		if (Omg::isGenerated($cid))
		{
			Omg::error("class $cid is already generated from " . Omg::getGenerated($cid));
		}
		Omg::setGenerated($cid, $this->getSchemaLocation());
		$className              = ucfirst($this->getClassName());
		$vars['className']      = $className;
		$vars['schemaLocation'] = $this->getSchemaLocation();
		
		$vars['trace'] = [];
		foreach (explode('<br>', Globals::getTrace(1)) as $item)
		{
			$item = trim(str_replace('<br />', '', $item));
			if ($item)
			{
				$vars['trace'][] = $item;
			}
		}
		
		$extender        = $this->getExtender();
		$vars['extends'] = $extender ? " extends $extender" : '';
		
		$ns                = $this->getNamespace("../");
		$vars['namespace'] = "namespace $ns;";
		
		
		$src = Tpl::load($this->getVariable('template'), $vars);
		
		//region usages
		$re = '/([|?( ])\\\\' . join('\\\\', explode('\\', $ns)) . '\\\\/m';
		if (preg_match($re, $src, $matches))
		{
			$src = preg_replace($re, '$1', $src);
		}
		
		$re     = '/([|?( ])\\\\' . join('\\\\', explode('\\', $this->getNamespace('/'))) . '\\\\(\w|\\\\)+/m';
		$match  = preg_match_all($re, $src, $matches);
		$usages = [];
		if ($match)
		{
			foreach ($matches[0] as $key => $matchStr)
			{
				$rns       = substr($matchStr, strlen($matches[1][$key]) + 1);
				$lastSlash = strrpos($rns, '\\');
				$rep       = substr($rns, $lastSlash + 1);
				$uns       = substr($rns, 0, $lastSlash);
				if ($className == $rep)
				{
					$prevName       = ucfirst($this->extractName($uns));
					$rep            = "$prevName$rep";
					$unscn          = " AS $rep";
					$usages[$rns][] = $unscn;
				}
				else
				{
					$usages[$uns][] = $rep;
				}
				
				$src = str_replace($matchStr, $matches[1][$key] . $rep, $src);
			}
		}
		$usagesStr = [];
		foreach ($usages as $uns => $classes)
		{
			$classes = array_unique(array_values($classes));
			if (count($classes) > 1)
			{
				$usagesStr[] = "use $uns\\{" . join(', ', $classes) . '};';
			}
			else
			{
				if (strpos($classes[0], ' AS ') !== false)
				{
					$usagesStr[] = "use $uns$classes[0];";
				}
				else
				{
					$usagesStr[] = "use $uns\\$classes[0];";
				}
			}
		}
		if (!$usagesStr)
		{
			$vars['usages'] = Tpl::REMOVE_LINE;
		}
		else
		{
			$vars['usages'] = join("\n", $usagesStr);
		}
		$src = str_replace('%usages%', $vars['usages'], $src);
		//endregion
		
		$newLes = [];
		foreach (explode("\n", $src) as $line)
		{
			if (strpos($line, Tpl::REMOVE_LINE) === false)
			{
				$newLes[] = $line;
			}
		}
		$src = join("\n", $newLes);
		
		$file = str_replace('\\', DIRECTORY_SEPARATOR, str_replace(Config::getRootNamespace() . '\\', '', $this->getNamespace("../") . "\\$className.php"));
		
		return self::makeFile($file, $src);
	}
	
	protected final function getReferenceClassPath(string $ref): string
	{
		if (Omg::isComponentResponse($ref))
		{
			$type = 'response';
		}
		elseif (Omg::isComponentSchema($ref))
		{
			$type = 'schema';
		}
		elseif (Omg::isComponentRequestBody($ref))
		{
			$type = 'requestBodies';
		}
		else
		{
			Omg::error('unknown reference');
		}
		
		return '\\' . $this->getNamespace('/component', $type, ucfirst($this->extractName($ref)));
	}
	
	protected final function extractName(string $from): string
	{
		return substr($from, strrpos(str_replace('\\', '/', $from), '/') + 1);
	}
	
	private final function constructPath(string $rootPath, array $currentPath, string ...$parts): string
	{
		if (!$parts)
		{
			$parts = [''];
		}
		array_walk($parts, function (&$p) { $p = str_replace('../', '[_CD_]', $p); });
		$parts[0]  = trim($parts[0]);
		$firstPart = strlen($parts[0]) > 0 ? $parts[0] : '';
		$className = $this->getClassName();
		
		if (substr($firstPart, 0, strlen($rootPath)) == $rootPath)
		{
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
		else
		{
			$root = $currentPath ?: [$rootPath];
		}
		
		$fullParts = array_merge($root, $parts);
		foreach ($fullParts as $key => $item)
		{
			if (!$item)
			{
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
		
		if (strpos($final, '.') !== false)
		{
			Omg::error('not allowed character in namespace:' . $final);
		}
		
		return str_replace('%className%', $className, $final);;
	}
	
	private final function cdNs(string $ns): string
	{
		$cdPos   = strpos($ns, '[_CD_]');
		$afterCd = substr($ns, $cdPos + 6);
		if ($cdPos !== false)
		{
			if ($afterCd)
			{
				if ($afterCd[0] != '\\')
				{
					$afterCd = '\\' . $afterCd;
				}
			}
			
			$firstCdPos = $cdPos;
			$prevPos    = $cdPos - 1;
			if ($ns[$prevPos] == '\\')
			{
				$firstCdPos--;
			}
			$tmpNs = substr($ns, 0, $firstCdPos);
			
			return $this->cdNs(substr($ns, 0, strrpos($tmpNs, '\\')) . $afterCd);
		}
		
		return $ns;
	}
	
	//region namespace helpers
	
	public function setNamespace(string ...$ns)
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
	
	/**
	 * @param string ...$parts
	 * @see getNamespace
	 * @return string
	 */
	protected final function getClassName(string ...$parts): string
	{
		if ($parts)
		{
			$ar = explode('\\', $this->getNamespace(...$parts));
			
			return end($ar);
		}
		else
		{
			return end($this->namespace);
		}
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
	 * @return \Infira\omg\generator\SchemaArrayModel|\Infira\omg\generator\SchemaBlankModel|\Infira\omg\generator\SchemaObjectModel
	 */
	protected final function getGenerator($bodySchema, string $namespace, string $schemaLocation, string $type = null)
	{
		if ($bodySchema and !($bodySchema instanceof Reference) and !($bodySchema instanceof Schema))
		{
			Omg::error('$bodySchema must be Reference or Schema ' . get_class($bodySchema) . ' was given');
		}
		if ($type and !in_array($type, ['object', 'array', 'auto'], true))
		{
			Omg::error("unknown type('$type')");
		}
		if ($bodySchema instanceof Reference)
		{
			if (!Omg::isComponentRef($bodySchema->getReference()))
			{
				Omg::notImplementedYet();
			}
			$generator = Omg::getGenerator($type, $this->getNamespace($namespace), $this->getSchemaLocation($schemaLocation));
			$generator->setExtender($this->getReferenceClassPath($bodySchema->getReference()));
		}
		else
		{
			if ($bodySchema)
			{
				Omg::validateSchema($bodySchema);
				if ($type == 'auto')
				{
					$type = $bodySchema->type;
				}
				elseif ($type == null)
				{
					Omg::error('type undefined');
				}
			}
			$generator = Omg::getGenerator($type, $this->getNamespace($namespace), $schemaLocation, $bodySchema);
		}
		
		return $generator;
	}
	
	protected final function convertToPhpType(string $type): string
	{
		$convertTypes = ['integer' => 'int', 'number' => 'float', 'boolean' => 'bool'];
		if (isset($convertTypes[$type]))
		{
			return $convertTypes[$type];
		}
		
		return $type;
	}
	
	protected final function getLibClassPath(string $lib): string
	{
		return $this->getFullClassPath("/lib/$lib");
	}
	
	//endregion
	
	
	//region variables
	protected final function makePhpDocType(string $phpType, ?string $dataClass, bool $nullable): string
	{
		$docValueType = [];
		if ($nullable)
		{
			$docValueType[] = 'null';
		}
		if ($phpType == 'singleClass')
		{
			$docValueType[] = $dataClass;
		}
		elseif ($dataClass)
		{
			$docValueType[] = 'array';
			$docValueType[] = '\stdClass';
			$docValueType[] = $dataClass;
		}
		else
		{
			$docValueType = [$this->convertToPhpType($phpType)];
		}
		
		return join('|', $docValueType);
	}
	
	protected final function makePhpArgumentType(?string $argType, ?string $dataClass, bool $nullable): string
	{
		if ($argType == 'singleClass')
		{
			$argType = $dataClass;
		}
		else
		{
			if ($dataClass)
			{
				return '';
			}
			else
			{
				$argType = $this->convertToPhpType($argType);
				if ($nullable and $argType)
				{
					$argType = "?$argType";
				}
			}
		}
		
		return $argType;
	}
	
	protected final function setVariable(string $name, $value)
	{
		$this->variables[$name] = $value;
	}
	
	protected final function getVariable(string $name) { return $this->variables[$name]; }
	
	protected final function add2Variable(string $name, $value) { $this->variables[$name][] = $value; }
	
	protected final function setTemplate(string $name) { $this->setVariable('template', $name); }
	
	public function setExtender(string $value) { $this->setVariable('extender', $value); }
	
	public function getExtender(): string { return $this->getVariable('extender'); }
	//region
}