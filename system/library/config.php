<?php if ( !defined( 'SECURITY_CONST' ) ) die( 'Access Denied' );

/**
	Class: Config

	Configuration handler.

	Author:
		Charly Lersteau

	Date:
		2011-08-05
*/
class Config
{
	const SEPARATOR = '.';

	protected static $_data = array();

	/**
		Method: get

		Gets the value of a configuration option.

		Parameters:
			$name - (optional) (string) The option name.
			$default - (optional) (mixed) A default value (default : null).

		Returns:
			(mixed)

		Example:
			>	Config::set( 'a.b.c', 1 );
			>
			>	$all = Config::get();
			>	$a = Config::get( 'a' );
			>	$c = Config::get( 'a.b.c' );
			>
			>	var_dump( $all, $a, $c );
			>	// array( 'a' => array( 'b' => array( 'c' => 1 ) ) )
			>	// array( 'b' => array( 'c' => 1 ) )
			>	// 1
	*/
	public static function get( $name = '', $default = null )
	{
		return self::_get(
			self::$_data,
			strtok( $name, self::SEPARATOR ),
			$default
		);
	}

	/**
		Method: set

		Sets the value of a configuration option.

		Parameters:
			$name - (string) The option name.
			$value - (mixed) The value for the option.

		Example:
			>	Config::set( 'a.b', 1 );
			>	Config::set( 'a.c', 2 );
			>
			>	$a = Config::get( 'a' );
			>	var_dump( $a );
			>	// array( 'b' => 1, 'c' => 2 )
	*/
	public static function set( $name, $value )
	{
		self::_set(
			self::$_data,
			strtok( $name, self::SEPARATOR ),
			$value
		);
	}

	/**
		Method: remove

		Remove a configuration option.

		Parameters:
			$name - (string) The option name.
	*/
	public static function remove( $name )
	{
		self::_remove( self::$_data, strtok( $name, self::SEPARATOR ) );
	}

	// Recursive get
	protected static function _get( &$data, $tok, $default )
	{
		if ( isset( $data ) )
		{
			return ( $tok !== false )
				? self::_get( $data[ $tok ], strtok( self::SEPARATOR ), $default )
				: $data;
		}
		return $default;
	}

	// Recursive set
	protected static function _set( &$data, $tok, $value )
	{
		if ( $tok !== false )
		{
			!isset( $data ) and $data = array();
			self::_set( $data[ $tok ], strtok( self::SEPARATOR ), $value );
		}
		else
		{
			$data = $value;
		}
	}

	// Recursive remove
	protected static function _remove( &$data, $tok )
	{
		if ( isset( $data[ $tok ] ) )
		{
			$next = strtok( self::SEPARATOR );
			if ( $next !== false )
			{
				self::_remove( $data[ $tok ], $next );
			}
			else
			{
				unset( $data[ $tok ] );
			}
		}
	}
}
