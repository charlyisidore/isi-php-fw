<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Cookie

	Cookie handler with chaining methods.

	Author:
		Charly Lersteau

	Date:
		2012-04-12

	Example:
		>	Cookie::factory( 'foo' )
		>		->value( 'bar' )
		>		->expire( '+1 hour' )
		->		->set();
*/
class Cookie
{
	protected $_name;
	protected $_value    = '';
	protected $_expire   = 0;
	protected $_path     = '/';
	protected $_domain   = false;
	protected $_secure   = false;
	protected $_httponly = false;

	/**
		Constructor: __construct

		Parameters:
			$name - (optional) (string) The cookie name.
	*/
	public function __construct( $name = null )
	{
		$this->name( $name );
	}

	/**
		Method: factory

		Create a new Cookie instance.

		Parameters:
			$name - (optional) (string) The cookie name.

		Returns:
			(self) A new Cookie instance.
	*/
	static public function factory( $name = null )
	{
		return new self( $name );
	}

	/**
		Method: name

		Sets the name of the cookie.

		Parameters:
			$name - (string) The name of the cookie.
	*/
	public function name( $name = null )
	{
		return $this->_property( '_name', $name );
	}

	/**
		Method: value

		Sets the value of the cookie.

		Parameters:
			$value - (string) The value of the cookie (default: '').
	*/
	public function value( $value = null )
	{
		return $this->_property( '_value', $value );
	}

	/**
		Method: expire

		Sets the time the cookie expires.

		Parameters:
			$expire - (string) A date/time string.
	*/
	public function expire( $expire = null )
	{
		if ( is_string( $expire ) )
		{
			$expire = strtotime( $expire );
		}
		return $this->_property( '_expire', $expire );
	}

	/**
		Method: path

		Sets the path on the server in which the cookie will be available on.

		Parameters:
			$path - (string) The path (default: '/').
	*/
	public function path( $path = null )
	{
		return $this->_property( '_path', $path );
	}

	/**
		Method: domain

		Sets the domain that the cookie is available to.

		Parameters:
			$domain - (string) The domain (default: false).
	*/
	public function domain( $domain = null )
	{
		return $this->_property( '_domain', $domain );
	}

	/**
		Method: secure

		Indicates that the cookie should only be transmitted over a secure
		HTTPS connection from the client.

		Parameters:
			$secure - (bool) (default: false).
	*/
	public function secure( $secure = null )
	{
		return $this->_property( '_secure', $secure );
	}

	/**
		Method: httponly

		When TRUE the cookie will be made accessible only through the HTTP
		protocol.

		Parameters:
			$httponly - (bool) (default: false).
	*/
	public function httponly( $httponly = null )
	{
		return $this->_property( '_httponly', $httponly );
	}

	/**
		Method: set

		Send a cookie.

		Returns:
			(bool)
	*/
	public function set()
	{
		return setcookie(
			$this->_name,
			$this->_value,
			$this->_expire,
			$this->_path,
			$this->_domain,
			$this->_secure,
			$this->_httponly
		);
	}

	/**
		Method: delete

		Delete a cookie.

		Returns:
			(bool)
	*/
	public function delete()
	{
		return $this
			->expire( time() - 3600 )
			->value( '' )
			->set();
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
