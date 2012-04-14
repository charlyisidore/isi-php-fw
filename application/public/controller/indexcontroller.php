<?php

/**
	Class: IndexController
*/
class IndexController
{
	public function __invoke()
	{
		echo 'Hello, world !';
	}

	public function notfound( $path )
	{
		echo "{$path} not found.";
	}
}

