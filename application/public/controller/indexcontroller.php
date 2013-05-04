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
		echo $view;
	}

	public function __404( $path )
	{
		echo "{$path} not found.";
	}
}

