<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Config

	Configuration handler.

	Author:
		Charly Lersteau

	Date:
		2012-04-12
*/
class Config
{
	static protected $_separator = '.';
	static protected $_data = array();

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
	static public function get( $name = '', $default = null )
	{
		return self::_get(
			self::$_data,
			strtok( $name, self::$_separator ),
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
	static public function set( $name, $value )
	{
		self::_set(
			self::$_data,
			strtok( $name, self::$_separator ),
			$value
		);
	}

	/**
		Method: remove

		Remove a configuration option.

		Parameters:
			$name - (string) The option name.
	*/
	static public function remove( $name )
	{
		self::_remove( self::$_data, strtok( $name, self::$_separator ) );
	}

	/**
		Method: separator

		Set the separator for multidimensional key names.

		Parameters:
			$separator - (string) The separator.
	*/
	static public function separator( $separator = null )
	{
		!isset( $separator ) or self::$_separator = $separator;
		return self::$_separator;
	}

	// Recursive get
	static protected function _get( &$data, $tok, $default )
	{
		if ( $tok !== false )
		{
			return isset( $data[ $tok ] )
				? self::_get( $data[ $tok ], strtok( self::$_separator ), $default )
				: $default;
		}
		return $data;
	}

	// Recursive set
	static protected function _set( &$data, $tok, $value )
	{
		if ( $tok !== false )
		{
			isset( $data ) or $data = array();
			self::_set( $data[ $tok ], strtok( self::$_separator ), $value );
		}
		else
		{
			$data = $value;
		}
	}

	// Recursive remove
	static protected function _remove( &$data, $tok )
	{
		if ( isset( $data[ $tok ] ) )
		{
			$next = strtok( self::$_separator );
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
