<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: View

	A class to build template engines.

	Author:
		Charly Lersteau

	Date:
		2013-04-06

	Example:
		>	$view = View::factory( 'article.php' );
		>
		>	// Quickly assign one parameter at once
		>	$view->title = 'My blog post';
		>
		>	// Assign one parameter at once
		>	$view->set( 'content', 'Here my content' );
		>
		>	// Assign many parameters
		>	$view->set( array(
		>		'date' => '2012-01-15',
		>		'author' => 'john' )
		>	);
		>
		>	// Render
		>	echo $view;
*/
abstract class View
{
	static protected $_directory = ''; // Base directory

	protected $_file;                 // File to load
	protected $_parameters = array(); // Assigned parameters

	/**
		Method: factory

		Create a new View instance.

		Parameters:
			$file - (string) The view file name.

		Returns:
			(self)
	*/
	static public function factory( $file )
	{
		if ( strpos( $file, self::directory() ) !== 0 )
		{
			$file = self::directory().DIRECTORY_SEPARATOR.$file;
		}

		$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		$className = $extension.'View';

		// Use autoload to find the class.
		return new $className( $file );
	}

	/**
		Method: file

		Get file name.

		Returns:
			(string)
	*/
	public function file()
	{
		return $this->_file;
	}

	/**
		Method: get

		Get value of a parameter.

		Parameters:
			$name - (optional) (string) The name of the parameter.

		Returns:
			(mixed)
	*/
	public function get( $name = null )
	{
		if ( isset( $name ) )
		{
			return isset( $this->_parameters[ $name ] )
				? $this->_parameters[ $name ]
				: $default;
		}
		return $this->_parameters;
	}

	/**
		Method: set

		Set value of parameters.

		Parameters:
			$name - (string|array) The parameter name or an associative array.
			$value - (optional) (mixed) The value.

		Returns:
			(self)
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
			$this->_parameters[ $name ] = $value;
		}
		return $this;
	}

	/**
		Method: remove

		Remove a parameter.

		Parameters:
			$name - (string) The name of the parameter.

		Returns:
			(self)
	*/
	public function remove( $name )
	{
		unset( $this->_parameters[ $name ] );
		return $this;
	}

	/**
		Method: toString

		Render the view.

		Returns:
			(string) The rendered content.
	*/
	abstract public function toString();

	/**
		Method: __toString

		Please use toString() because Exceptions cannot be thrown from
		__toString().
	*/
	public function __toString()
	{
		return $this->toString();
	}

	/**
		Method: directory

		Set the view container directory.

		Parameters:
			$directory - (string) The directory.
	*/
	static public function directory( $directory = null )
	{
		!isset( $directory ) or self::$_directory = $directory;
		return self::$_directory;
	}

	// Hidden constructor.
	protected function __construct( $file )
	{
		$this->_file = realpath( $file );
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
		return isset( $this->_parameters[ $name ] );
	}

	// Alias of remove.
	public function __unset( $name )
	{
		$this->remove( $name );
	}
}

/**
	Class: PHPView

	A minimal PHP template engine.

	Extends:
		<View>

	Example:
		>	<div class="article">
		>		<h2><?php echo $title; ?></h2>
		>		<p class="author">Date: <?php echo $date; ?></p>
		>		<p class="date">Author: <?php echo $author; ?></p>
		>		<div class="content"><?php echo $content; ?></div>
		>	</div>
*/
class PHPView extends View
{
	/**
		Method: toString
	*/
	public function toString()
	{
		extract( $this->get() );
		ob_start();
		include $this->file();
		$data = ob_get_contents();
		ob_end_clean();
		return (string)$data;
	}
}

