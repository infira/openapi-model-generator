<?php
{$namespace}

abstract class Operation
{
	private   $method;
	private   $path;
	private   $operationID;
	private   $responses = [];
	private   $requestBodyClass;
	protected $activeResponseHttpCode;
	
	public function __construct(?string $method, ?string $path, ?string $operationID)
	{
		$this->method      = $method;
		$this->path        = $path;
		$this->operationID = $operationID;
	}
	
	protected function registerRequestBody(string $class)
	{
		$this->requestBodyClass = $class;
	}
	
	protected function registerResponse(string $httpCode, string $class)
	{
		$name                   = "res$httpCode";
		$this->responses[$name] = ['class' => $class, 'httpCode' => $httpCode];
	}
	
	public function __get(string $name)
	{
		if (array_key_exists($name, $this->responses) or $name == '{$operationInputParameterName}')
		{
			$this->$name = Storage::NOT_SET;
			
			return $this->$name;
		}
		else
		{
			$this->error("variable $name not found");
		}
	}
	
	public function __set(string $name, $value)
	{
		if ($name == '{$operationInputParameterName}')
		{
			$class    = $this->requestBodyClass;
			$this->{$operationInputParameterName} = new $class($value);
			
			return $this->$name;
		}
		elseif (array_key_exists($name, $this->responses))
		{
			$class                        = $this->responses[$name]['class'];
			$this->activeResponseHttpCode = $this->responses[$name]['httpCode'];
			$this->$name                  = new $class($value);
			
			return $this->$name;
		}
		else
		{
			$this->error("variable $name not found");
		}
	}
	
	public final function hasRequestBody(): bool
	{
		return $this->requestBodyClass !== null;
	}
	
	/**
	 * @return string|null
	 */
	public final function getOperationID(): ?string
	{
		return $this->operationID;
	}
	
	public final function is(string $operationID): bool
	{
		return $this->operationID === $operationID;
	}
	
	/**
	 * @return string|null
	 */
	public final function getPath(): ?string
	{
		return $this->path;
	}
	
	/**
	 * @return string|null
	 */
	public final function getMethod(): ?string
	{
		return $this->method;
	}

	public final function getResponse(): \stdClass
	{
		if (!$this->activeResponseHttpCode)
		{
			$this->error('response not set');
		}
		$res       = new \stdClass();
		$res->code = $this->activeResponseHttpCode;
		$name      = "res$this->activeResponseHttpCode";
		$res->res  = $this->$name;

		return $res;
	}

	private function error(string $msg)
	{
		throw new \Exception($msg);
	}
}