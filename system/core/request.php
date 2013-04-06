<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Request

	Internal request handler.

	Author:
		Charly Lersteau

	Date:
		2013-04-01
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

		$result = call_user_func_array( $callback, $this->_parameters );

		array_pop( self::$_stack );
		return $result;
	}

	/**
		Method: __toString

		Do the request once and cache it.
	*/
	public function __toString()
	{
		isset( $this->_response ) or $this->_response = $this->__invoke();
		return (string)$this->_response;
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
