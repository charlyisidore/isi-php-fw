<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Request

	Internal request handler.

	Author:
		Charly Lersteau

	Date:
		2013-07-19
*/
class Request
{
	// Store all current requests
	static protected $_stack = array();

	protected $_path;
	protected $_target;
	protected $_parameters;
	protected $_response = null;

	/**
		Constructor: Request

		Parameters:
			$path - (string) A path.
	*/
	public function __construct( $path )
	{
		$this->_path   = $path;
		$this->_target = Route::get( $path, $this->_parameters );
	}

	/**
		Method: factory

		Create a new Request instance.

		Parameters:
			$path - (string) A path.

		Returns:
			(self) A new Request instance.
	*/
	static public function factory( $path )
	{
		return new self( $path );
	}

	/**
		Method: path

		Get request path.

		Returns:
			(string)
	*/
	public function path()
	{
		return $this->_path;
	}

	/**
		Method: target

		Get request target.

		Returns:
			(mixed)
	*/
	public function target()
	{
		return $this->_target;
	}

	/**
		Method: parameters

		Get request parameters.

		Returns:
			(array)
	*/
	public function parameters()
	{
		return $this->_parameters;
	}

	/**
		Method: __invoke

		Do the request.
	*/
	public function __invoke()
	{
		array_push( self::$_stack, $this );

		// Convert array(string, string) to array(object, string) if method is not static.
		$callback = $this->_target;

		if ( is_array( $callback ) and is_string( $callback[0] ) )
		{
			$class  = $callback[0];
			$method = $callback[1];

			// If array(string, string) points to a non-static function,
			// we instanciate the class.
			$rm = new ReflectionMethod( $class, $method );
			if ( !$rm->isStatic() )
			{
				$obj = new $class( $this );
				$callback = array( $obj, $method );
			}
		}

		if ( !is_callable( $callback ) )
		{
			return null;
		}

		$result = call_user_func_array( $callback, $this->_parameters );

		array_pop( self::$_stack );
		return $result;
	}

	/**
		Method: toString

		Do the request once and cache it.
	*/
	public function toString()
	{
		isset( $this->_response ) or $this->_response = $this->__invoke();

		if ( is_callable( array( $this->_response, 'toString' ) ) )
		{
			return $this->_response->toString();
		}
		else if ( is_string( $this->_response ) or is_callable( array( $this->_response, '__toString' ) ) )
		{
			return (string)$this->_response;
		}
		else if ( isset( $this->_response ) )
		{
			// PHP 5 >= 5.2.0, PECL json >= 1.2.0
			// but we can use JSON.php if needed
			return json_encode( $this->_response );
		}
		return '';
	}

	/**
		Method: __toString
	*/
	public function __toString()
	{
		return $this->toString();
	}

	/**
		Method: main

		Get main request.
	*/
	static public function main()
	{
		return reset( self::$_stack );
	}

	/**
		Method: current

		Get current request.
	*/
	static public function current()
	{
		return end( self::$_stack );
	}
}
