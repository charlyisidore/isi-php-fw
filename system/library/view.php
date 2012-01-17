<?php if ( !defined( 'SECURITY_CONST' ) ) die( 'Access Denied' );

/**
	Class: View

	A class to build template engines.

	Author:
		Charly Lersteau

	Date:
		2012-01-17

	Example:
		>	$view = new View( 'article.php' );
		>
		>	// Quickly assign one variable at once
		>	$view->title = 'My blog post';
		>
		>	// Assign one variable at once
		>	$view->set( 'content', 'Here my content' );
		>
		>	// Assign many variables
		>	$view->set( array(
		>		'date' => '2012-01-15',
		>		'author' => 'john' )
		>	);
		>
		>	// Render
		>	echo $view;
*/
class View
{
	public static $_dir = '.';

	protected $_file;                 // File to load
	protected $_variables  = array(); // Assigned variables

	/**
		Constructor: __construct
	*/
	public function __construct( $file )
	{
		$this->_file = self::$_dir.'/'.$file;
	}

	/**
		Method: factory

		Create a new View instance.

		Returns:
			(self) A new View instance.
	*/
	public static function factory( $name )
	{
		return new self( $name );
	}

	/**
		Method: get

		Retrieve config variables.

		Parameters:
			$name - (string) The variable name.

		Returns:
			self
	*/
	public function get( $name )
	{
		return isset( $this->_variables[ $name ] )
			? $this->_variables[ $name ]
			: $default;
	}

	/**
		Method: set

		Set variables.

		Parameters:
			$name - (string|array) The variable name or an associative array.
			$value - (optional) (mixed) The value.

		Returns:
			self
	*/
	public function set( $name, $value = null )
	{
		if ( is_array( $name ) )
		{
			foreach ( $name as $n => $v )
			{
				$this->set( $n, $v );
			}
		}
		else
		{
			$this->_variables[ $name ] = $value;
		}
		return $this;
	}

	/**
		Method: remove

		Remove a variable.

		Parameters:
			$name - (string) The variable name.

		Returns:
			self
	*/
	public function remove( $name )
	{
		unset( $this->_variables[ $name ] );
		return $this;
	}

	/**
		Method: __toString

		Render the view.

		Returns:
			(string) The rendered content.
	*/
	public function __toString()
	{
		$extension = strtolower( pathinfo( $this->_file, PATHINFO_EXTENSION ) );
		$className = $extension.'View';

		$view = new $className( $this->_file );
		$view->set( $this->_variables );

		return (string)$view;
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
		return isset( $this->_variables[ $name ] );
	}

	// Alias of remove.
	public function __unset( $name )
	{
		$this->remove( $name );
	}
}

