<?php if ( !defined( 'SECURITY_CONST' ) ) die( 'Access Denied' );

/**
	Class: Input

	Functions for accessing form and cookie data.

	Author:
		Charly Lersteau

	Date:
		2011-08-05
*/
class Input
{
	const SEPARATOR = '.';

	/**
		Method: get

		GET data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	public static function get( $key = '', $default = null )
	{
		return self::_get( $_GET, strtok( $key, self::SEPARATOR ), $default );
	}

	/**
		Method: post

		POST data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	public static function post( $key = '', $default = null )
	{
		return self::_get( $_POST, strtok( $key, self::SEPARATOR ), $default );
	}

	/**
		Method: files

		FILES data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	public static function files( $key = '', $default = null )
	{
		return self::_get( $_FILES, strtok( $key, self::SEPARATOR ), $default );
	}

	/**
		Method: cookie

		COOKIE data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	public static function cookie( $key = '', $default = null )
	{
		return self::_get( $_COOKIE, strtok( $key, self::SEPARATOR ), $default );
	}

	/**
		Method: request

		REQUEST data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	public static function request( $key = '', $default = null )
	{
		return self::_get( $_REQUEST, strtok( $key, self::SEPARATOR ), $default );
	}

	/**
		Method: server

		SERVER data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	public static function server( $key = '', $default = null )
	{
		return self::_get( $_SERVER, strtok( $key, self::SEPARATOR ), $default );
	}

	/**
		Method: method

		Request method (lowercase).

		Returns:
			(string)
	*/
	public static function method()
	{
		return strotolower( self::server( 'REQUEST_METHOD', 'get' ) );
	}

	/**
		Method: isAjax

		Check if it is a XHR request.

		Returns:
			(bool)
	*/
	public static function isAjax()
	{
		return isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] )
			and strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) == 'xmlhttprequest';
	}

	/**
		Method: ip

		Return real IP address of the client.

		Returns:
			(string)
	*/
	public static function ip()
	{
		return
		self::server( 'HTTP_X_FORWARDED_FOR',
			self::server( 'HTTP_CLIENT_IP',
				self::server( 'REMOTE_ADDR', '0.0.0.0' )
			)
		);
	}

	/**
		Method: merge

		Merge $_FILES and $_POST into the same $_POST structure.
	*/
	public static function merge()
	{
		$files = array(
			'name'     => array(),
			'type'     => array(),
			'tmp_name' => array(),
			'error'    => array(),
			'size'     => array()
		);

		// Flip the first level with the second
		foreach ( $_FILES as $key_a => $data_a )
		{
			foreach ( $data_a as $key_b => $data_b )
			{
				$files[ $key_b ][ $key_a ] = $data_b;
			}
		}

		// Merge and make the first level the deepest level
		foreach ( $files as $type => $data )
		{
			self::_merge( $type, $data, $_POST );
		}
	}

	// Recursive get
	protected static function _get( &$data, $tok, $default )
	{
		if ( isset( $data ) )
		{
			return ( $tok !== false )
				? self::_get( $data[ $tok ], strtok( self::SEPARATOR ), $default )
				: self::_escape( $data );
		}
		return $default;
	}

	// Check magic quotes
	protected static function _escape( $value )
	{
		return get_magic_quotes_gpc()
			? self::_stripslashes( $value )
			: $value;
	}

	// Recursive stripslashes
	protected static function _stripslashes( $value )
	{
		return is_array( $value )
			? array_map( array( 'self', __FUNCTION__ ), $value )
			: stripslashes( $value );
	}

	// Recursive $_FILES and $_POST merging
	protected static function _merge( $type, $file, &$post )
	{
		foreach ( $file as $key => $value )
		{
			if ( !isset( $post[ $key ] ) )
			{
				$post[ $key ] = array();
			}
			if ( is_array( $value ) )
			{
				self::_merge( $type, $value, $post[ $key ] );
			}
			else
			{
				$post[ $key ][ $type ] = $value;
			}
		}
	}
}
