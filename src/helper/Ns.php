<?php

namespace Infira\omg\helper;

use Infira\omg\Omg;

class Ns
{
	private $namespace = [];
	
	private $rootPath = '';
	/**
	 * @var string
	 */
	private $seperator;
	
	public function __construct(string $rootPath = null, string $seperator = '\\')
	{
		$this->rootPath  = $rootPath;
		$this->seperator = $seperator;
	}
	
	private final function constructPath(array $currentPath, string ...$parts): string
	{
		if (!$parts) {
			$parts = [''];
		}
		array_walk($parts, function (&$p) { $p = str_replace('../', '[_CD_]', $p); });
		$parts[0]  = trim($parts[0]);
		$firstPart = strlen($parts[0]) > 0 ? $parts[0] : '';
		$className = $this->getClassName();
		$rootPath  = $this->rootPath;
		
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
			$item            = str_replace('/', $this->seperator, $item);
			$item            = str_replace('\\', $this->seperator, $item);
			$item            = str_replace('\\\\\\', $this->seperator, $item);
			$item            = str_replace('\\\\', $this->seperator, $item);
			$fullParts[$key] = $item;
		}
		$final = join($this->seperator, $fullParts);
		$final = $this->cdNs($final);
		
		if (strpos($final, '.') !== false) {
			Omg::error('not allowed character in namespace:' . $final);
		}
		
		return str_replace('%className%', $className, $final);
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
	
	public function set(string ...$parts): self
	{
		$this->namespace = explode($this->seperator, $this->get(...$parts));
		
		return $this;
	}
	
	public function getClassName(): string
	{
		return end($this->namespace);
	}
	
	public function getFullClassPath(string ...$parts): string
	{
		$ar        = explode('\\', $this->get(...$parts));
		$className = ucfirst($ar[array_key_last($ar)]);
		
		return '\\' . join('\\', array_merge(array_slice($ar, 0, -1), [$className]));
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
	public function get(string ...$parts): string
	{
		return $this->constructPath($this->namespace, ...$parts);
	}
}