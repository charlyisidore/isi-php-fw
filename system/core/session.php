<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Session

	Session handler.

	Author:
		Charly LERSTEAU

	Date:
		2012-04-13
*/
abstract class Session
{
	const ERROR_NOT_STARTED = 'Session: Session not started';

	static protected $_data = null;

	/*
		Method: get

		Retrieve a session variable.

		Parameters:
			$name - (optional) (string) A key.
			$default - (optional) (mixed) A default value (default: null).

		Returns:
			(string)
	*/
	static public function get( $name = null, $default = null )
	{
		if ( isset( $name ) )
		{
			return isset( self::$_data->{$name} ) ? self::$_data->{$name} : $default;
		}
		return self::$_data;
	}

	/*
		Method: set

		Register one or more global variables with the current session.

		Parameters:
			$name - (string|array) A key or an associative array.
			$value - (optional) (string) A value.
	*/
	static public function set( $name, $value = null )
	{
		if ( is_array( $name ) )
		{
			foreach ( $name as $n => $v )
			{
				self::set( $n, $v );
			}
		}
		else if ( isset( self::$_data ) )
		{
			self::$_data->{$name} = $value;
		}
		else
		{
			trigger_error( self::ERROR_NOT_STARTED );
		}
	}

	/*
		Method: delete

		Unregister a global variable from the current session.

		Parameters:
			$name - (string) A key.
	*/
	static public function delete( $name )
	{
		unset( self::$_data->{$name} );
	}

	/*
		Method: start

		Start new or resume existing session.

		Parameters:
			$handler - (optional) (string) Driver name (default: 'php').
	*/
	static public function start( $handler = 'php' )
	{
		$class = $handler.'Session';
		self::$_data = new $class;
	}

	/*
		Method: destroy

		Destroys all data registered to a session.
	*/
	static public function destroy()
	{
		if ( isset( self::$_data ) )
		{
			self::$_data->__destroy();
			self::$_data = null;
		}
	}

	// Interface
	abstract public function __destroy();
	abstract public function __toString();
}

// Default handler
class PHPSession extends Session
{
	public function __construct()
	{
		if ( session_id() == '' )
		{
			session_start();
		}
	}

	public function __get( $name )
	{
		return isset( $_SESSION[ $name ] ) ? $_SESSION[ $name ] : null;
	}

	public function __set( $name, $value )
	{
		$_SESSION[ $name ] = $value;
	}

	public function __unset( $name )
	{
		unset( $_SESSION[ $name ] );
	}

	public function __toString()
	{
		$encode = session_encode();
		return is_string( $encode ) ? $encode : '';
	}

	public function __destroy()
	{
		// Unset all of the session variables.
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if ( ini_get( 'session.use_cookies' ) )
		{
			$params = session_get_cookie_params();
			setcookie(
				session_name(), '', time() - 42000,
				$params[ 'path' ],   $params[ 'domain' ],
				$params[ 'secure' ], $params[ 'httponly' ]
			);
		}
		session_destroy();
	}
}

