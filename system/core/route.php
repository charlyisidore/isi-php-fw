<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Route

	A dispatcher.

	Author:
		Charly Lersteau

	Date:
		2013-07-19

	Example:
		>	// This function will be caught in index.php/hello/<name>
		>	function hello( $name ) { echo "Hello, {$name} !"; }
		>
		>	// This function will be caught when page is not found
		>	function error404( $path ) { echo "Page {$path} not found"; }
		>
		>	// Using standard constructor
		>	$route = new Route( '/hello/:name', 'hello' )
		>	$route->parameters( array( ':name' => '[a-z]+' ) );
		>
		>	// ... or using factory
		>	Route::factory( '/hello/:name', 'hello' )
		>		->parameters( array( ':name' => '[a-z]+' ) );
		>
		>	// Setting default target
		>	Route::notfound( 'error404' );
		>
		>	// ... or setting default target using integer 404
		>	new Route( 404, 'error404' );
		>
		>	// Retrieve target
		>	$target = Route::get( $_SERVER[ 'PATH_INFO' ] );
		>
		>	// Retrieve callback and arguments
		>	$callback  = $target[0];
		>	$arguments = $target[1];
*/
class Route
{
	const ERROR_UNSUPPORTED = 'Route: Unsupported target for path "%s"';

	static protected $_data    = array(); // Route collection
	static protected $_default = null;    // Default target

	protected $_path       = null;    // (string)
	protected $_target     = null;    // (callback)
	protected $_parameters = array(); // (array(item => regex))

	/*
		Constructor: Route

		Append a route to the Route collection.

		Parameters:
			$path - (string) A path.
			$target - (callback) Target callback.
	*/
	public function __construct( $path, $target )
	{
		// integer 404 is special path to default target.
		if ( $path === 404 )
		{
			self::notfound( $target );
		}
		else if ( is_callable( $target ) )
		{
			$this->_path   = $path;
			$this->_target = $target;
			self::$_data[] = $this;
		}
		// We can build multiple routes to class or object's methods
		else if ( is_object( $target ) or ( is_string( $target ) and class_exists( $target ) ) )
		{
			self::_buildFromClass( $path, $target );
		}
		else
		{
			trigger_error( sprintf( self::ERROR_UNSUPPORTED, $path ) );
		}
	}

	/**
		Method: factory

		Create a new Route instance.

		Parameters:
			$path - (string) A path.
			$target - (callback|string) Target.

		Returns:
			(self) A new Route instance.
	*/
	static public function factory( $path, $target = null )
	{
		return new self( $path, $target );
	}

	/**
		Method: target

		Set the target function or method or class.

		Parameters:
			$target - (callback) The target.
	*/
	public function target( $target = null )
	{
		return $this->_property( '_target', $target );
	}

	/**
		Method: parameters

		Set the parameters.

		Parameters:
			$parameters - (array) The parameters.
	*/
	// We don't use a "parameter(n,v)" method because arg positions are ambiguous.
	public function parameters( $parameters = null )
	{
		return $this->_property( '_parameters', $parameters );
	}

	/**
		Method: __toString

		Get the regex of the route.

		Returns:
			(string)
	*/
	public function __toString()
	{
		$parameters = $this->_parameters;
		foreach ( $parameters as &$value )
		{
			$value = "({$value})";
		}
		return strtr( $this->_path, $parameters );
	}

	/**
		Method: get

		Get the target callback of a path or default value if not found.

		Parameters:
			$path - (string) A path.
			$arguments - (array) If it is provided, it will be filled with arguments.

		Returns:
			(callback) The target.
	*/
	static public function get( $path = null, &$arguments = null )
	{
		if ( !isset( $path ) ) return self::$_data; // To debug

		foreach ( self::$_data as $route )
		{
			$regex = '`^'.str_replace( '`', '\\`', $route->__toString() ).'$`';

			if ( preg_match( $regex, $path, $arguments ) )
			{
				// $arguments[0] is the whole string.
				array_shift( $arguments );
				// We convert numeric arguments to integer or float.
				array_walk( $arguments, array( 'self', '_settype' ) );
				return $route->target();
			}
		}

		$arguments = array( $path );
		return self::$_default;
	}

	/**
		Method: notfound

		Set the default target.

		Parameters:
			$default - (callback) Default target.
	*/
	static public function notfound( $default = null )
	{
		!isset( $default ) or self::$_default = $default;
		return self::$_default;
	}

	// Retrieve or store a property
	protected function _property( $name, $value )
	{
		if ( isset( $value ) )
		{
			$this->{$name} = $value;
		}
		else
		{
			return $this->{$name};
		}
		return $this;
	}

	// Build new routes with a path and a class name or an object instance.
	// Each valid public method will generate a route.
	static protected function _buildFromClass( $path, $class )
	{
		$rc = new ReflectionClass( $class );

		// Abstract classes cannot be instanciated.
		if ( $rc->isAbstract() ) return;

		$methods = $rc->getMethods( ReflectionMethod::IS_PUBLIC );

		foreach ( $methods as $m )
		{
			if ( !$m->isConstructor() and !$m->isDestructor() )
			{
				$valid       = true;
				$routePath   = "{$path}/{$m->name}";
				$routeTarget = array( $class, $m->name );
				$routeParams = array();
				$parameters  = $m->getParameters();

				foreach ( $parameters as $p )
				{
					// Type hinting is not supported for scalars.
					// If parameter has fixed types or pass by ref,
					// method will not be correctly callable.
					if ( $p->isPassedByReference()
						or $p->isArray() // PHP 5 >= 5.1.0
						or self::_gettype( $p ) )
					{
						$valid = false;
						break;
					}

					$routeParams[ "{{$p->name}}" ] = '.+';
					$routePath .= $p->isOptional() // PHP 5 >= 5.0.3
						? "(?:/{{$p->name}})?"
						: "/{{$p->name}}";
				}

				if ( $valid )
				{
					Route::factory( $routePath, $routeTarget )
						->parameters( $routeParams );
				}
			}
		}
	}

	// Common regex of PHP functions, variables, classes, etc.
	const PHP_FUNC_REGEX = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

	// Get the type of a reflection parameter. Return false if undefined.
	static protected function _gettype( $p )
	{
		// To get the type we have to convert $p to a string and parse it.
		$pattern = '`('.self::PHP_FUNC_REGEX.')\s+\\$'.$p->name.'`i';
		$subject = $p->__toString();

		return ( preg_match( $pattern, $subject, $matches )
			and isset( $matches[1] ) ) ? $matches[1] : false;
	}

	// Affect the good type to a value.
	static protected function _settype( &$value )
	{
		if ( ctype_digit( $value ) )
		{
			$value = intval( $value );
		}
		else if ( is_numeric( $value ) )
		{
			$value = floatval( $value );
		}
	}

	// Check if a path is absolute or relative.
	static protected function _absolute( $path )
	{
		return strpos( $path, '/' ) === 0;
	}
}

