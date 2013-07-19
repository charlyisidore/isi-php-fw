<?php

define( 'SECURITY_CONST', true );
define( 'SYS_PATH', realpath( dirname( __FILE__ ) ) );

require_once 'core/autoload.php';
require_once 'core/input.php';
require_once 'core/request.php';
require_once 'core/route.php';
require_once 'core/view.php';

/**
	Class: Launcher

	Default application launcher.

	Author:
		Charly Lersteau

	Date:
		2013-07-19
*/
class Launcher
{
	const LIBRARY    = 'library';
	const CONFIG     = 'config';
	const CONTROLLER = 'controller';
	const VIEW       = 'view';

	static private $_application = null;

	/**
		Method: run

		Run an application.

		Parameters:
			$application - (string) Directory of the application.
			$config - (string) Name of the configuration.
	*/
	static public function run( $application, $config = null )
	{
		!isset( self::$_application ) or exit( 'Multiple Launcher Access Denied' );
		self::$_application = $application;

		$ds  = DIRECTORY_SEPARATOR;
		$cwd = dirname( $_SERVER[ 'SCRIPT_FILENAME' ] );

		// Register autoload function to load following classes
		Autoload::initialize();

		// Set autoload directories (controller, app & sys library)
		Autoload::register( array(
			$cwd.$ds.$application.$ds.self::CONTROLLER,
			$cwd.$ds.$application.$ds.self::LIBRARY,
			SYS_PATH.$ds.self::LIBRARY
		) );

		// Set the default view directory
		View::directory( $cwd.$ds.$application.$ds.self::VIEW );

		// Load the configuration file
		if ( isset( $config ) )
		{
			require_once $cwd.$ds.$application.$ds.self::CONFIG.$ds.$config.'.php';
		}

		// Retrieve the request and process it
		$path    = Input::path();
		$request = new Request( $path );
		echo $request->toString();
	}
}

