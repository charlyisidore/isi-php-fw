<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Event

	Event handler.

	Author:
		Charly Lersteau

	Date:
		2012-04-12

	Example:
		>	class Logger {
		>		public function onEvent( $message ) {
		>			$event = Event::current();
		>			echo 'Event type: '.$event->type().".\n";
		>			echo "Logger: {$message}.\n";
		>		}
		>	}
		>
		>	function interceptor() {
		>		$event = Event::current();
		>		$event->stop();
		>	}
		>
		>	function mailer( $message ) {
		>		$event = Event::current();
		>		echo "Mailer: {$message}.\n";
		>		echo "Param: {$event->param}.\n";
		>	}
		>
		>	// Connecting observers.
		>	Event::attach( 'something',
		>		array( new Logger(), 'onEvent' ),
		>		'interceptor',
		>		'mailer'
		>	);
		>
		>	// Sending the event with parameters.
		>	$event = new Event( 'something' );
		>	$event->notify( 'hello' );
		>
		>	// Output "Event type: something\n"
		>	// Output "Logger: hello."
		>
		>	// Disconnecting an observer.
		>	Event::detach( 'something', 'interceptor' );
		>
		>	// Sending the event with parameters.
		>	Event::factory( 'something' )
		>		->set( 'param', 'mydata' )
		>		->notify( 'world' );
		>
		>	// Output "Event type: something\n"
		>	// Output "Logger: world."
		>	// Output "Mailer: world."
		>	// Output "Param: mydata."
*/
class Event
{
	static protected $_observers = array();
	static protected $_current; // (self)

	protected $_type; // (string)
	protected $_stop; // (bool)
	protected $_properties = array();

	/**
		Constructor: __construct

		Parameters:
			$type - (string) The event type.
	*/
	public function __construct( $type )
	{
		$this->_type = $type;
	}

	/**
		Method: factory

		Create a new Event instance.

		Parameters:
			$type - (string) The event type.

		Returns:
			(self)
	*/
	static public function factory( $type )
	{
		return new self( $type );
	}

	/**
		Method: current

		Return the current Event instance.

		Returns:
			(self)
	*/
	static public function current()
	{
		return self::$_current;
	}

	/**
		Method: attach

		Attach observers to an event type.

		Parameters:
			$type - (string) Event type.
			... - (callback) Callable functions.

		Example:
			>	Event::attach( 'login', array( new Logger(), 'update' ) );
	*/
	static public function attach( $type )
	{
		$arguments = func_get_args();
		array_shift( $arguments );

		if ( !isset( self::$_observers[ $type ] ) )
		{
			self::$_observers[ $type ] = array();
		}
		foreach ( $arguments as $observer )
		{
			if ( !in_array( $observer, self::$_observers[ $type ] ) )
			{
				self::$_observers[ $type ][] = $observer;
			}
		}
	}

	/**
		Method: detach

		Detach observers from an event type.

		Parameters:
			$type - (string) Event type.
			... - (callback) Callable functions.
	*/
	static public function detach( $type )
	{
		$arguments = func_get_args();
		array_shift( $arguments );

		if ( !empty( self::$_observers[ $type ] ) )
		{
			foreach ( $arguments as $observer )
			{
				$keys = array_keys( self::$_observers[ $type ], $observer );
				foreach ( $keys as $key )
				{
					unset( self::$_observers[ $type ][ $key ] );
				}
			}
		}
	}

	/**
		Method: notify

		Notify all observers with optional arguments.

		Returns:
			self
	*/
	public function notify()
	{
		if ( !empty( self::$_observers[ $this->_type ] ) )
		{
			$observers = self::$_observers[ $this->_type ];
			$arguments = func_get_args();
			$this->_stop = false;

			foreach ( $observers as $observer )
			{
				self::$_current = $this;
				call_user_func_array( $observer, $arguments );

				if ( $this->_stop )
					break;
			}

			self::$_current = null;
		}
		return $this;
	}

	/**
		Method: type

		Return the event type or null if there is no current Event.

		Returns:
			(string)
	*/
	public function type()
	{
		return $this->_type;
	}

	/**
		Method: stop

		Stop event propagation.

		Returns:
			self
	*/
	public function stop()
	{
		$this->_stop = true;
		return $this;
	}

	/**
		Method: get

		Retrieve a property.

		Parameters:
			$name - (string) The property name.
			$default - (mixed) A default value (null if unspecified).

		Returns:
			(mixed) The property value, or the default value if undefined.

		Examples:
			>	$username = $event->get( 'username' );

			>	// Using __get magic method.
			>	$username = $event->username;
	*/
	public function get( $name, $default = null )
	{
		return isset( $this->_properties[ $name ] )
			? $this->_properties[ $name ]
			: $default;
	}

	/**
		Method: set

		Store a property.

		Parameters:
			$name - (string) The property name.
			$value - (mixed) The property value.

		Returns:
			self

		Examples:
			>	// Using chaining methods.
			>	$event = Event::factory( 'login' )
			>		->set( 'username', $username )
			>		->set( 'password', $password );
			>		->notify();

			>	// Using __set magic method.
			>	$event = new Event( 'login' );
			>	$event->username = $username;
			>	$event->password = $password;
			>	$event->notify( $event );
	*/
	public function set( $name, $value )
	{
		$this->_properties[ $name ] = $value;
		return $this;
	}

	/**
		Method: remove

		Remove a property.

		Parameters:
			$name - (string) The property name.

		Returns:
			self
	*/
	public function remove( $name )
	{
		unset( $this->_properties[ $name ] );
		return $this;
	}

	// Alias of get.
	public function __get( $name )
	{
		return $this->get( $name );
	}

	// Alias of set.
	public function __set( $name, $value )
	{
		$this->set( $name, $value );
	}

	// Check existence.
	public function __isset( $name )
	{
		return isset( $this->_properties[ $name ] );
	}

	// Alias of remove.
	public function __unset( $name )
	{
		$this->remove( $name );
	}
}
