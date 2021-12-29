<?php
//%namespace%

abstract class RObject extends Storage
{
	protected $properties = [];
	
	//region magic methods
	public function __call($key, $arguments)
	{
		$this->validateKey($key);
		$this->$key->fill(...$arguments);
	}
	
	public function __get(string $key)
	{
		return $this->get($key);
	}
	
	public function __set(string $key, $value)
	{
		$this->set($key, $value);
	}
	
	//endregion
	
	/**
	 * @return string[]
	 */
	public function getProperties(): array
	{
		return array_keys($this->properties);
	}
	
	protected function propertyExists(string $key): bool
	{
		return isset($this->properties[$key]);
	}
	
	protected function getConfig(string $key)
	{
		return $this->properties[$key];
	}
	//endregion
	
	//region abstractions
	protected function validateFiller(array &$filler)
	{
		if (!$this->fillNonExistingWithDefaultValues)
		{
			return;
		}
		if (gettype(array_key_first($filler)) != 'string')
		{
			$this->error("object can take only associative array");
		}
		foreach ($this->properties as $propKey => $conf)
		{
			if (!array_key_exists($propKey, $filler))
			{
				$def = $this->getItemRealDefaultValue($propKey, true, self::NOT_SET);
				if ($def !== self::NOT_SET)
				{
					$filler[$propKey] = $def;
				}
			}
		}
	}
	
	protected function validateStorage(?string $parentKey, array $data, bool $propertiesAreMandatory = false): array
	{
		$mandatoryProperties = [];
		$originalData        = $data;
		foreach ($this->properties as $propKey => $conf)
		{
			$required = $conf['req'];
			if ($propertiesAreMandatory)
			{
				$required = true;
			}
			if (!array_key_exists($propKey, $data))
			{
				$def = $this->getItemRealDefaultValue($propKey, false, self::NOT_SET);
				if ($def !== self::NOT_SET)
				{
					$data[$propKey] = $def;
				}
				elseif ($required)
				{
					$mandatoryProperties[] = $propKey;
				}
			}
		}
		if ($mandatoryProperties)
		{
			$this->error('mandatory fields[' . join(', ', $mandatoryProperties) . '] not set', $parentKey);
		}
		
		return $data;
	}
	
	protected function validateKey(string $key)
	{
		if (!$this->propertyExists($key))
		{
			$this->error('does not exist in this schema', $key);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getItemConfig(string $key, string $conf = null)
	{
		if (!$this->propertyExists($key))
		{
			$this->error('does not exist in this schema', $key);
		}
		if ($conf != null)
		{
			if (!array_key_exists($conf, $this->properties[$key]))
			{
				$this->error("config('$conf') does not exist", $key);
			}
			
			return $this->properties[$key][$conf];
		}
		
		return $this->properties[$key];
	}
	
	//endregion
}