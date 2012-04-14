<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Input

	Functions for accessing form and cookie data.

	Author:
		Charly Lersteau

	Date:
		2012-04-12
*/
class Input
{
	static protected $_separator = '.';

	/**
		Method: method

		Request method (lowercase).

		Returns:
			(string)
	*/
	static public function method()
	{
		return strotolower( self::server( 'REQUEST_METHOD', 'get' ) );
	}

	/**
		Method: get

		GET data.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	static public function get( $key = '', $default = null )
	{
		return self::_get( $_GET, strtok( $key, self::$_separator ), $default );
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
	static public function post( $key = '', $default = null )
	{
		return self::_get( $_POST, strtok( $key, self::$_separator ), $default );
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
	static public function files( $key = '', $default = null )
	{
		return self::_get( $_FILES, strtok( $key, self::$_separator ), $default );
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
	static public function cookie( $key = '', $default = null )
	{
		return self::_get( $_COOKIE, strtok( $key, self::$_separator ), $default );
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
	static public function request( $key = '', $default = null )
	{
		return self::_get( $_REQUEST, strtok( $key, self::$_separator ), $default );
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
	static public function server( $key = '', $default = null )
	{
		return self::_get( $_SERVER, strtok( $key, self::$_separator ), $default );
	}

	/*
		Method: path

		Any client-provided pathname information trailing the actual script
		filename but preceding the query string (default: '').
		Removes trailing and double '/'.

		Example:
			> /controller/action/arg1/arg2
			in
			> http://site.com/path/controller/action/arg1/arg2?a=1&b=2
			or
			> http://site.com/path/index.php/controller/action/arg1/arg2?a=1&b=2

		Returns:
			(string)
	*/
	static public function path()
	{
		if ( isset( $_SERVER[ 'PATH_INFO' ] ) )
		{
			$path = $_SERVER[ 'PATH_INFO' ];
		}
		else if ( isset( $_SERVER[ 'ORIG_PATH_INFO' ] ) )
		{
			$path = str_replace(
					$_SERVER[ 'SCRIPT_NAME' ],
					'',
					$_SERVER[ 'ORIG_PATH_INFO' ]
				);
		}
		else if ( isset( $_ENV[ 'ORIG_PATH_INFO' ] ) )
		{
			$path = str_replace(
					$_SERVER[ 'SCRIPT_NAME' ],
					'',
					$_ENV[ 'ORIG_PATH_INFO' ]
				);
		}
		return '/'.( isset( $path ) ? preg_replace( '`/+`', '/', trim( $path, '/' ) ) : '' );
	}

	/**
		Method: submit

		Parse a POST key in the form "key[value1][value2]...[valueN]".

		Parameters:
			$key - (string) The key name.
			$default - (optional) (mixed) A default value (default: array())

		Returns:
			(array)
	*/
	static public function submit( $key, $default = array() )
	{
		$post = is_array( $key ) ? $key : self::post( $key );
		if ( is_array( $post ) )
		{
			$submit = array();
			foreach(
				new RecursiveIteratorIterator(
					new RecursiveArrayIterator( $post ),
					RecursiveIteratorIterator::SELF_FIRST
				)
				as $key => $value )
			{
				$submit[] = $key;
			}
			return $submit;
		}
		return $default;
	}

	/**
		Method: ajax

		Check if it is a XHR request.

		Returns:
			(bool)
	*/
	static public function ajax()
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
	static public function ip()
	{
		return
		self::server( 'HTTP_X_FORWARDED_FOR',
			self::server( 'HTTP_CLIENT_IP',
				self::server( 'REMOTE_ADDR', '0.0.0.0' )
			)
		);
	}

	/**
		Method: header

		HTTP headers.

		Parameters:
			$key - (optional) (string) The key name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(array|string)
	*/
	static public function header( $key = null, $default = null )
	{
		if ( isset( $key ) )
		{
			$key = 'HTTP_'.strtr( strtoupper( $key ), '-', '_' );
			return isset( $_SERVER[ $key ] ) ? $_SERVER[ $key ] : $default;
		}
		else
		{
			$result = array();
			foreach ( $_SERVER as $key => $value )
			{
				if ( strpos( $key, 'HTTP_' ) === 0 )
				{
					$name = strtr( strtolower( substr( $key, 5 ) ), '_', '-' );
					$result[ $name ] = $value;
				}
			}
			return $result;
		}
	}

	/**
		Method: merge

		Merge $_FILES and $_POST into the same $_POST structure.
	*/
	static public function merge()
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

	// Check magic quotes
	static protected function _escape( $value )
	{
		return get_magic_quotes_gpc()
			? self::_stripslashes( $value )
			: $value;
	}

	// Recursive stripslashes
	static protected function _stripslashes( $value )
	{
		return is_array( $value )
			? array_map( array( 'self', __FUNCTION__ ), $value )
			: stripslashes( $value );
	}

	// Recursive $_FILES and $_POST merging
	static protected function _merge( $type, $file, &$post )
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
