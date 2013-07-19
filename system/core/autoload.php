<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Autoload

	A class to load PHP files automatically.

	Author:
		Charly Lersteau

	Date:
		2013-07-19
*/
class Autoload
{
	static protected $_path = array();

	/**
		Method: initialize

		Register Autoload::load as an auto-loader function.
	*/
	static public function initialize()
	{
		// PHP 5 >= 5.1.2
		spl_autoload_register( array( 'Autoload', 'load' ) );
	}

	/**
		Method: register

		Append an include directory.

		Parameters:
			$path - (string) A local directory path.
	*/
	static public function register( $path = null )
	{
		if ( isset( $path ) )
		{
			if ( is_array( $path ) )
			{
				foreach ( $path as $p )
				{
					self::register( $p );
				}
			}
			else if ( !in_array( $path, self::$_path ) and file_exists( $path ) and is_dir( $path ) )
			{
				self::$_path[] = $path;
			}
		}
		return self::$_path;
	}

	/**
		Method: unregister

		Remove an include directory.

		Parameters:
			$path - (string) A local directory path.
	*/
	static public function unregister( $path )
	{
		self::$_path = array_diff( self::$_path, is_array( $path ) ? $path : array( $path ) );
	}

	/**
		Method: load

		Attempt to load undefined class.

		Parameters:
			$class - (string) Name of the class to load.
	*/
	static public function load( $class )
	{
		// Remove every invalid character (like '/' or '.') for security reasons
		$class = preg_replace( '`[^a-z0-9_]*`', '', strtolower( $class ) );
		foreach ( self::$_path as $path )
		{
			$file = $path.'/'.$class.'.php';
			if ( file_exists( $file ) )
			{
				include_once $file;
				return;
			}
		}
	}
}
