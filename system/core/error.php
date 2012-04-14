<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

// Some constant definitions for PHP < 5.2.0
defined( 'E_RECOVERABLE_ERROR' ) or define( 'E_RECOVERABLE_ERROR', 4096 );
defined( 'E_DEPRECATED' )        or define( 'E_DEPRECATED',        8192 );
defined( 'E_USER_DEPRECATED' )   or define( 'E_USER_DEPRECATED',  16384 );

/**
	Class: Error

	Error handling.

	Author:
		Charly Lersteau

	Date:
		2012-04-12
*/
class Error
{
	const UNKNOWN = 'Unknown Error';

	static protected $_data     = array();
	static protected $_register = array();
	static protected $_typestr  = array (
		E_ERROR              => 'Error',
		E_WARNING            => 'Warning',
		E_PARSE              => 'Parsing Error',
		E_NOTICE             => 'Notice',
		E_CORE_ERROR         => 'Core Error',
		E_CORE_WARNING       => 'Core Warning',
		E_COMPILE_ERROR      => 'Compile Error',
		E_COMPILE_WARNING    => 'Compile Warning',
		E_USER_ERROR         => 'User Error',
		E_USER_WARNING       => 'User Warning',
		E_USER_NOTICE        => 'User Notice',
		E_STRICT             => 'Strict Notice',
		E_RECOVERABLE_ERROR  => 'Recoverable Error',
		E_DEPRECATED         => 'Deprecated Notice',
		E_USER_DEPRECATED    => 'User Deprecated Notice'
	);

	protected $_type    = E_USER_NOTICE;
	protected $_message = '';
	protected $_file    = null;
	protected $_line    = null;

	/**
		Constructor: Error
	*/
	public function __construct( $message, $type = E_USER_NOTICE, $file = null, $line = null )
	{
		$this->type   ( $type );
		$this->message( $message );
		$this->file   ( $file );
		$this->line   ( $line );
	}

	/**
		Method: factory

		Create a new Error instance.

		Returns:
			(self)
	*/
	static public function factory( $message, $type = E_USER_NOTICE, $file = null, $line = null )
	{
		return new self( $message, $type, $file, $line );
	}

	/**
		Method: type
	*/
	public function type( $type = null )
	{
		return $this->_property( '_type', $type );
	}

	/**
		Method: message
	*/
	public function message( $message = null )
	{
		return $this->_property( '_message', $message );
	}

	/**
		Method: file
	*/
	public function file( $file = null )
	{
		return $this->_property( '_file', $file );
	}

	/**
		Method: line
	*/
	public function line( $line = null )
	{
		return $this->_property( '_line', $line );
	}

	/**
		Method: __toString
	*/
	public function __toString()
	{
		$type = $this->type();
		$typestr = isset( self::$_typestr[ $type ] ) ? self::$_typestr[ $type ] : self::UNKNOWN;
		return sprintf(
			"%s [%d] \"%s\" in \"%s\" on line %d\n",
			$typestr, $type, $this->message(), $this->file(), $this->line()
		);
	}

	/**
		Method: initialize

		Setup error handler.
	*/
	static public function initialize()
	{
		set_error_handler( array( 'Error', '_handler' ) );
		register_shutdown_function( array( 'Error', '_shutdown' ) );
	}

	/**
		Method: register

		Register a new error event handler.

		Parameters:
			$callback - (callback) A function handler (argument: Error instance).
	*/
	static public function register( $callback = null )
	{
		!isset( $callback ) or self::$_register[] = $callback;
		return self::$_register;
	}

	/**
		Method: last

		Retrieve all or last Error instances.

		Parameters:
			$last - (optional) (int) Number of errors to retrieve.

		Returns:
			(array)
	*/
	static public function last( $last = 0 )
	{
		return array_slice( self::$_data, 0 - $last );
	}

	// Error handler
	static public function _handler( $type, $message, $file, $line )
	{
		$error = new self( $message, $type, $file, $line );
		self::$_data[] = $error;

		foreach ( self::$_register as $callback )
		{
			call_user_func( $callback, $error );
		}
		return !empty( self::$_register );
	}

	// Fatal error handler
	static public function _shutdown()
	{
		// PHP >= 5.2.0
		if ( function_exists( 'error_get_last' ) )
		{
			$error = error_get_last();
			if ( isset( $error ) )
			{
				self::_handler(
					$error[ 'type' ],
					$error[ 'message' ],
					$error[ 'file' ],
					$error[ 'line' ]
				);
			}
		}
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
}
