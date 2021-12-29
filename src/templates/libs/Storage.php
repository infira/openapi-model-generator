<?php
//%namespace%

abstract class Storage
{
	const NOT_SET = '--NOT_SET--';
	protected $parentKey                        = null;
	protected $nullable                         = false;
	protected $propertiesAreMandatory           = false;
	protected $fillNonExistingWithDefaultValues = false;
	protected $filledValue                      = self::NOT_SET;
	
	/**
	 * @var \ArrayObject
	 */
	public $storage;
	
	/**
	 * @var Storage
	 */
	protected $parent;
	
	//region magic
	
	/**
	 * @throws \Exception
	 */
	public function __construct($filler = self::NOT_SET)
	{
		$this->storage = new \ArrayObject();
		$this->fill($filler);
	}
	
	public function __isset($key)
	{
		return $this->exists($key);
	}
	
	public function __unset($key)
	{
		$this->unset($key);
	}
	
	public function __toString(): string
	{
		return serialize($this->getData());
	}
	
	public function __debugInfo()
	{
		return $this->storage->getArrayCopy();
	}
	//endregion
	
	//region public data handlers
	
	/**
	 * Get final array with intended constructed values
	 *
	 * @param bool|null $propertiesAreMandatory - treat all object properties as mandatory(useful for response), if null then default value will be used
	 * @return array
	 */
	public function getData(bool $propertiesAreMandatory = null): array
	{
		$propertiesAreMandatory = $propertiesAreMandatory === null ? $this->propertiesAreMandatory : $propertiesAreMandatory;
		
		return $this->constructFinalData(null, $propertiesAreMandatory);
	}
	
	private function constructFinalData(?string $parentKey, bool $propertiesAreMandatory)
	{
		if ($this->filledValue !== self::NOT_SET)
		{
			return $this->filledValue;
		}
		$output = [];
		foreach ($this->storage as $key => $item)
		{
			if ($this->isDataObject($item['vt']))
			{
				if ($item['io'] == true)
				{
					$outputItem = $item['rv'];
				}
				elseif ($item['rv']->parent)
				{
					$outputItem = $item['rv']->constructFinalData(null, $propertiesAreMandatory);
				}
				else
				{
					$outputItem = $item['rv']->constructFinalData($key, $propertiesAreMandatory);
				}
			}
			else
			{
				$outputItem = $item['rv'];
			}
			$output[$key] = $outputItem;
		}
		
		return $this->validateStorage($parentKey, $output, $propertiesAreMandatory);
	}
	
	/**
	 * @param array|\stdClass|RArray|RObject|$filler
	 * @throws \Exception
	 * @return $this
	 */
	public function fill($filler): self
	{
		if ($filler === self::NOT_SET)
		{
			return $this;
		}
		if ($filler === null)
		{
			if (!$this->nullable)
			{
				$this->error('filler cannot be null');
			}
			$this->filledValue = null;
		}
		if (empty($filler))
		{
			return $this;
		}
		if ($filler instanceof \stdClass)
		{
			$finalFiller = (array)$filler;
		}
		elseif (is_array($filler))
		{
			$finalFiller = $filler;
		}
		elseif (is_object($filler) and $filler instanceof Storage)
		{
			$finalFiller = $filler->getData(false);
		}
		else
		{
			$this->error("filler must be one of types[array,stdClass,RObject,RArray] type('" . $this->getPhpType($filler) . "' was given");
		}
		
		if (!isset($finalFiller))
		{
			$this->error('$finalFiller NOT set');
		}
		
		if (!is_array($finalFiller))
		{
			$this->error('filler must be array');
		}
		
		$this->validateFiller($finalFiller);
		foreach ($finalFiller as $key => $value)
		{
			if ($this->amiObject() and !$this->propertyExists($key))
			{
				continue;
			}
			$this->set($key, $value);
		}
		
		return $this;
	}
	
	public function get(string $key)
	{
		$this->validateKey($key);
		$itemValueType = $this->getItemValueType($this->amiArray() ? self::NOT_SET : $key);
		
		if ($this->exists($key))
		{
			return $this->storage[$key]['rv'];
		}
		
		if ($this->isDataObject($itemValueType))
		{
			$cls = $this->createItemDataModel($key);
			$rv  = &$cls;
		}
		elseif ($this->getItemIsNullable($key))
		{
			$rv = null;
		}
		elseif ($this->getItemDefaultValue($key) !== self::NOT_SET)
		{
			$rv = $this->getItemDefaultValue($key);
		}
		else
		{
			$this->error('trying to retrieve value which does not exists', $key);
		}
		
		if ($this->isDataObject($itemValueType))
		{
			$this->storage->offsetSet($key, ['vt' => $itemValueType, 'rv' => $rv, 'io' => false]);
		}
		else
		{
			$this->storage->offsetSet($key, ['vt' => $itemValueType, 'rv' => $rv]);
		}
		
		return $this->storage[$key]['rv'];
	}
	
	public function set(string $key, $value)
	{
		$this->validateKey($key);
		$itemValueType  = $this->getItemValueType($key);
		$realType       = $this->getPhpType($value);
		$itemConfig     = $this->getItemConfig($key);
		$itemIsNullable = $this->getItemIsNullable($key);
		
		
		//region item value validation
		if ($value === null)
		{
			if (!$itemIsNullable)
			{
				$this->error('value cannot be null', $key);
			}
		}
		elseif ($this->isItemEnum($key))
		{
			$enum = $this->getItemEnum($key);
			if (!in_array($value, $enum, true))
			{
				$this->error('allowed values are [' . join(', ', $enum) . "], '$value' was given ", $key);
			}
		}
		elseif (in_array($itemValueType, ['int', 'float']) and (is_numeric($value) or $value == ''))
		{
			$f = $itemValueType . 'val';
			
			$value = $f($value);
			
			if (array_key_exists('min', $itemConfig) and $value < $itemConfig['min'])
			{
				$min = $itemConfig['min'];
				$this->error("value must be >= $min, $value was given", $key);
			}
			if (array_key_exists('max', $itemConfig) and $value > $itemConfig['max'])
			{
				$max = $itemConfig['max'];
				$this->error("value must be <= $max, $value was given", $key);
			}
		}
		elseif ($itemValueType == 'bool' and is_numeric($value) and ($value == 1 or $value == 0 or $value == ''))
		{
			$value = $value == 1;
		}
		elseif ($itemValueType == 'string')
		{
			$value = "$value";
		}
		elseif ($itemValueType != $realType and in_array($itemValueType, ['object', 'array']))
		{
			if (is_object($value) and $value instanceof \stdClass)
			{
				$value = (array)$value;
			}
			elseif (is_object($value) and !$this->isItemDataModel($key, $value))
			{
				$this->error("can take only " . $this->getItemDataModelClassName($key) . ' objects', $key);
			}
			elseif (is_array($value) and gettype(array_key_first($value)) != 'string' and $itemValueType == 'object')
			{
				$this->error("object can take only associative array", $key);
			}
			elseif (is_array($value) and gettype(array_key_first($value)) == 'string' and $itemValueType == 'array')
			{
				$this->error("can take only sequential array", $key);
			}
		}
		elseif ($itemValueType != $realType)
		{
			$this->error("value must be type('$itemValueType') type('$realType') was given", $key);
		}
		//endregion
		
		if ($this->isDataObject($itemValueType))
		{
			$io = false; //is override
			if (is_object($value) and $this->isItemDataModel($key, $value))
			{
				$cls = $value;
				$cls->setParent($this, $key);
				$rv = &$cls;
			}
			elseif ($value === null and $itemIsNullable)
			{
				$rv = null;
				$io = true;
			}
			elseif ((is_object($value) and $value instanceof \stdClass) or is_array($value))
			{
				$rv = $this->createItemDataModel($key, (array)$value);
			}
			else
			{
				$this->error('not implemented');
			}
			
			if (!$this->exists($key))
			{
				$this->storage->offsetSet($key, ['vt' => $itemValueType]);
			}
			$this->storage[$key]['rv'] = $rv;
			$this->storage[$key]['io'] = $io;
		}
		elseif (!$this->exists($key))
		{
			$this->storage->offsetSet($key, ['vt' => $itemValueType, 'rv' => $value]);
		}
		elseif ($this->exists($key))
		{
			$this->storage[$key]['rv'] = $value;
		}
		else
		{
			$this->error('not implemented');
		}
	}
	
	public function exists($key): bool { return $this->storage->offsetExists($key); }
	
	public function unset($key): self
	{
		$this->storage->offsetUnset($key);
		
		return $this;
	}
	
	public function count(): int { return $this->storage->count(); }
	
	public function ok(): bool { return $this->count() > 0; }
	
	public function hasValue($value): bool { return (array_search($value, $this->getData()) !== false); }
	
	public function flush() { $this->storage->exchangeArray([]); }
	
	public function createItemDataModel(string $key = null, $filler = self::NOT_SET): Storage
	{
		$class = $this->getItemDataModelClassName($key);
		/**
		 * @var $cls Storage
		 */
		$cls = new $class();
		$cls->setParent($this, $key);
		$cls->fill($filler);
		
		return $cls;
	}
	
	private function isItemDataModel(string $key, object $object): bool
	{
		$cn = $this->getItemDataModelClassName($key);
		
		return $object instanceof $cn;
	}
	//endregion
	
	//region data helpers
	private function isDataObject(string $valueType): bool { return in_array($valueType, ['object', 'array']); }
	
	protected function getItemRealDefaultValue(string $key, bool $voidArray = false, $undefinedDefaultValue = null)
	{
		if ($this->getItemDefaultValue($key) !== self::NOT_SET)
		{
			return $this->getItemDefaultValue($key);
		}
		elseif ($this->getItemIsNullable($key))
		{
			return null;
		}
		elseif ($this->getItemValueType($key) == 'array' and !$voidArray)
		{
			return [];
		}
		else
		{
			if ($undefinedDefaultValue === null)
			{
				$this->error('undefined real default value');
			}
			
			return $undefinedDefaultValue;
		}
	}
	
	private function setParent(&$parent, string $key)
	{
		$this->parent    = &$parent;
		$this->parentKey = $key;
	}
	//endregion
	
	//region other helpers
	private function getErrorTrace(array $trace): array
	{
		if ($this->parent !== null)
		{
			$trace[] = ($this->parent->amiArray() ? "[$this->parentKey]" : "->$this->parentKey");
			
			return $this->parent->getErrorTrace($trace);
		}
		else
		{
			$trace[] = get_class($this);
		}
		
		return $trace;
	}
	
	protected function error(string $msg, string $key = null)
	{
		$traceArr = array_reverse($this->getErrorTrace([]));
		if ($key)
		{
			$traceArr[] = $this->amiArray() ? "[$key]" : "->$key";
		}
		$trace = join('', $traceArr);
		$msg   = $trace . " says: " . $msg;
		throw new \Exception($msg);
	}
	
	protected function isObject($type): bool { return $type instanceof RObject; }
	
	protected function isArray($type): bool { return $type instanceof RArray; }
	
	protected function amiArray(): bool { return $this->isArray($this); }
	
	protected function amiObject(): bool { return $this->isObject($this); }
	
	private function getPhpType($value): string
	{
		$type         = gettype($value);
		$convertTypes = ['integer' => 'int', 'number' => 'float', 'boolean' => 'bool'];
		if (isset($convertTypes[$type]))
		{
			return $convertTypes[$type];
		}
		
		return $type;
	}
	//endregion
	
	//region item configs
	protected function getItemValueType(string $key): string
	{
		return $this->getItemConfig($key, 'vt');
	}
	
	protected function isItemEnum(string $key): bool
	{
		return $this->getItemEnum($key) !== null;
	}
	
	protected function getItemEnum(string $key): ?array
	{
		return $this->getItemConfig($key, 'enum');
	}
	
	protected function getItemIsNullable(string $key): bool
	{
		return $this->getItemConfig($key, 'nl');
	}
	
	protected function getItemDefaultValue(string $key)
	{
		return $this->getItemConfig($key, 'def');
	}
	
	protected function getItemDataModelClassName(string $key): ?string
	{
		return $this->getItemConfig($key, 'dm');
	}
	//endregion
	
	//region abstractions
	protected abstract function validateFiller(array &$filler);
	
	protected abstract function validateStorage(?string $parentKey, array $data, bool $propertiesAreMandatory = false): array;
	
	protected abstract function validateKey(string $key);
	
	/**
	 * @param string      $key
	 * @param string|null $conf
	 * @return array|string
	 */
	protected abstract function getItemConfig(string $key, string $conf = null);
	
	//endregion
}