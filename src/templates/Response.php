<?php
//%namespace%

class Response
{
	/**
	 * current http status code
	 *
	 * @var null|int
	 */
	public $httpStatusCode;
	
	/**
	 * If current response has no content
	 *
	 * @var bool
	 */
	public $noContent = false;
	
	/**
	 * @var Storage
	 */
	public $result;
}