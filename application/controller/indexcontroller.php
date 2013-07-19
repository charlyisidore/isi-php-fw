<?php

/**
	Class: IndexController
*/
class IndexController
{
	public function __invoke( $who = 'world' )
	{
		$view = View::factory( 'index.php' );
		$view->who = $who;
		return $view;
	}

	public function json( $who = 'world' )
	{
		header( 'Content-Type: application/json' );
		$obj = new stdclass;
		$obj->hello = $who;
		return $obj;
	}

	public function __404( $path )
	{
		$view = View::factory( '404.php' );
		$view->path = $path;
		return $view;
	}
}

