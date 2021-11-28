<?php


namespace gem\exceptions\fileZipper;


use Throwable;

class fileZipperException extends \Exception
{

	public function __construct( $message = "", $code = 0, Throwable $previous = null )
	{

		parent::__construct( $message, $code, $previous );
	}

}