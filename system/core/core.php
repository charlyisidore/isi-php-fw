<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

// System path
define( 'SYS_PATH', realpath( dirname( __FILE__ ).'/..' ) );

/**
	Class: Core

	Core bootstrap functions.

	Author:
		Charly Lersteau

	Date:
		2012-04-12
*/
class Core
{
	const SYSTEM     = 'system';
	const LIBRARY    = 'library';
	const CONFIG     = 'config';
	const CONTROLLER = 'controller';
	const VIEW       = 'view';

	static protected $_application = null;
	static protected $_config      = null;
	static protected $_load        = null;

	/**
		Method: run

		Run the application.

		Parameters:
			$application - (string) Name of the application.
			$config - (string) Name of the configuration.
	*/
	static public function run( $application, $config )
	{
		!isset( self::$_application ) or exit( 'Multiple Core Access Denied' );

		$ds = DIRECTORY_SEPARATOR;

		self::$_application = $application;
		self::$_config      = $config;

		Error::initialize();
		View::base( APP_PATH.$ds.self::$_application.$ds.self::VIEW );
		self::$_load = array(
			APP_PATH.$ds.self::$_application.$ds.self::CONTROLLER,
			APP_PATH.$ds.self::$_application.$ds.self::LIBRARY,
			SYS_PATH.$ds.self::LIBRARY
		);

		spl_autoload_register( array( 'Core', 'load' ) );

		require_once APP_PATH.$ds.self::$_application.$ds.self::CONFIG.$ds.self::$_config.'.php';

		echo new Request( Input::path() );
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
		foreach ( self::$_load as $directory )
		{
			$file = $directory.'/'.$class.'.php';
			if ( file_exists( $file ) )
			{
				include_once $file;
				return;
			}
		}
	}
}
