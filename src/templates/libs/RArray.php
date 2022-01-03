<?php
//%namespace%

abstract class RArray extends Storage implements \ArrayAccess, \Iterator
{
	private $itemConfig = [];
	private $position   = 0;
	
	public function __construct($filler = self::NOT_SET)
	{
		$this->position = 0;
		parent::__construct($filler);
	}
	
	/**
	 * @throws \Exception
	 */
	public function __get(string $key)
	{
		if ($this->amiArray())
		{
			$this->error('cant access property of array type dataClass', $key);
		}
	}
	
	/**
	 * @throws \Exception
	 */
	public function __set(string $key, $value)
	{
		if ($this->amiArray())
		{
			$this->error('cant set property of array type dataClass', $key);
		}
	}
	
	protected function setItemConfig(array $config)
	{
		$this->itemConfig = $config;
	}
	
	
	//region abstractions
	protected function validateFiller(array &$filler)
	{
		if (gettype(array_key_first($filler)) == 'string')
		{
			$this->error("array can take only sequential array");
		}
	}
	
	protected function validateStorage(?string $parentKey, array $data, bool $propertiesAreMandatory = false): array
	{
		return array_values($data);
	}
	
	protected function validateKey(string $key)
	{
		//just void
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getItemConfig(?string $key, string $conf = null)
	{
		if ($conf != null)
		{
			if (!array_key_exists($conf, $this->itemConfig))
			{
				$this->error("config('$conf') does not exist", $key);
			}
			
			return $this->itemConfig[$conf];
		}
		
		return $this->itemConfig;
	}
	
	//endregion
	//region ArrayAccess
	
	/**
	 * @inheritDoc
	 */
	public function offsetExists($offset): bool
	{
		return $this->exists($offset);
	}
	
	/**
	 * @inheritDoc
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
	
	/**
	 * @inheritDoc
	 */
	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
		{
			$offset = $this->count();
		}
		$this->set($offset, $value);
	}
	
	/**
	 * @inheritDoc
	 */
	public function offsetUnset($offset): self
	{
		return $this->unset($offset);
	}
	
	//endregion
	
	//region Iterator
	/**
	 * @inheritDoc
	 */
	public function current()
	{
		return $this->get($this->position);
	}
	
	/**
	 * @inheritDoc
	 */
	public function next()
	{
		++$this->position;
	}
	
	/**
	 * @inheritDoc
	 */
	public function key()
	{
		return $this->position;
	}
	
	/**
	 * @inheritDoc
	 */
	public function valid(): bool
	{
		return $this->offsetExists($this->position);
	}
	
	/**
	 * @inheritDoc
	 */
	public function rewind()
	{
		$this->position = 0;
	}
	//endregion
}