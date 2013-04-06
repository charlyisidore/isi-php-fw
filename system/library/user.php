<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: User

	User authentication library.

	Author:
		Charly LERSTEAU

	Date:
		2013-04-06
*/
class User
{
	protected static $_current  = null;
	protected static $_provider = null;
	protected static $_crypt    = 'sha1';
	protected $_id;
	protected $_username;
	protected $_password;
	protected $_data = array();

	const SESSION_ID = 'user_id';

	/**
		Method: create

		Returns:
			(self)
	*/
	public static function create()
	{
		return new User;
	}

	/**
		Method: get

		Parameters:
			$identifier - (optional) (int|string) An ID or username.

		Returns:
			(self|null)
	*/
	public static function get( $identifier = null )
	{
		return ( isset( $identifier ) and self::exists( $identifier ) )
			? new User( $identifier )
			: self::$_current;
	}

	/**
		Method: delete

		Parameters:
			$identifier - (int|string) An ID or username.
	*/
	public static function delete( $identifier )
	{
		return self::$_provider->delete( $identifier );
	}

	/**
		Method: all

		Get the list of user ID.

		Returns:
			(array)
	*/
	public static function all()
	{
		return self::$_provider->all();
	}

	/**
		Method: exists

		Returns:
			(bool)
	*/
	public static function exists( $identifier )
	{
		return self::$_provider->exists( $identifier );
	}

	/**
		Method: valid

		Returns:
			(bool)
	*/
	public static function valid()
	{
		return !empty( self::$_current );
	}

	/**
		Method: check

		Parameters:
			$identifier - (int|string) An ID or username.
			$password - (string) A password.

		Returns:
			(bool)
	*/
	public static function check( $identifier, $password )
	{
		$crypt = self::$_crypt;
		return self::$_provider->check( $identifier, $crypt( $password ) );
	}

	/*
		Method: login

		Parameters:
			$identifier - (int|string) An ID or username.
			$password - (string) A password.
			$throw - (bool) Throw an exception in case of failure.

		Throws:
			- UserLoginException
	*/
	public static function login( $identifier, $password, $throw = false )
	{
		if ( !User::check( $identifier, $password ) )
		{
			if ( $throw )
			{
				throw new UserLoginException( $identifier );
			}
			return false;
		}

		self::$_current = self::get( $identifier );
		Session::set( self::SESSION_ID, self::$_current->id() );
		return true;
	}

	/*
		Method: logout
	*/
	public static function logout()
	{
		self::$_current = null;
		Session::delete( self::SESSION_ID );
	}

	/**
		Method: id

		Returns:
			(int)
	*/
	public function id()
	{
		return $this->_id;
	}

	/**
		Method: username

		Set the username.

		Parameters:
			$username - (string) The username.
	*/
	public function username( $username = null )
	{
		if ( isset( $username ) )
		{
			$this->_username = $username;
		}
		else
		{
			return $this->_username;
		}
		return $this;
	}

	/**
		Method: password

		Set the password and encrypts it.

		Parameters:
			$password - (string) The password.
	*/
	public function password( $password = null )
	{
		if ( isset( $password ) )
		{
			$crypt = self::$_crypt;
			$this->_password = $crypt( $password );
		}
		else
		{
			return $this->_password;
		}
		return $this;
	}

	/**
		Method: data

		Returns:
			(mixed)
	*/
	public function data( $key = null, $value = null )
	{
		if ( is_array( $key ) )
		{
			$this->_data = $key;
		}
		else if ( isset( $value ) )
		{
			$this->_data->{$key} = $value;
		}
		else if ( isset( $key ) )
		{
			return isset( $this->_data->{$key} ) ? $this->_data->{$key} : null;
		}
		else
		{
			return $this->_data;
		}
		return $this;
	}

	/**
		Method: save

		Returns:
			(bool)
	*/
	public function save()
	{
		return self::$_provider->write( $this );
	}

	/**
		Method: initialize

		Initialize provider and session variables.
	*/
	public static function initialize( $provider = 'JSON' )
	{
		$provider = $provider.'User';
		self::$_provider = new $provider;

		Session::start();
		$id = Session::get( self::SESSION_ID );

		if ( isset( $id ) )
		{
			self::$_current = self::get( (int)$id );
		}
	}

	// Protected constructor
	protected function __construct( $identifier = null )
	{
		if ( isset( $identifier ) and $user = self::$_provider->read( $identifier ) )
		{
			$this->_id       = $user->id;
			$this->_username = $user->username;
			$this->_password = $user->password;
			$this->_data     = $user->data;
		}
	}
}

/**
	Class: UserException
*/
class UserException extends LogicException
{
	protected $identifier;

	public function __construct( $identifier, $message = 'UserException' )
	{
		$this->identifier = $identifier;
		parent::__construct( $message );
	}

	public function getIdentifier()
	{
		return $this->identifier;
	}
}

/**
	Class: UserLoginException
*/
class UserLoginException extends UserException
{
	public function __construct( $identifier )
	{
		$message = Lang::tr( 'UserLoginException', array( $identifier ) );
		parent::__construct( $identifier, $message );
	}
}

/**
	Class: JSONUser

	User authentication library using a JSON database.
	Suitable for small websites.
*/
class JSONUser
{
	protected static $_file = null;
	protected $_data;

	// Constructor
	public function __construct()
	{
		$this->_data = new stdclass;
		if ( !isset( self::$_file ) )
		{
			self::$_file = SYS_PATH.'/data/user.json';
		}
		if ( file_exists( self::$_file ) )
		{
			$this->_data = json_decode( file_get_contents( self::$_file ) );
		}
	}

	// Return all user ID
	public function all()
	{
		return array_keys( $this->_data );
	}

	// Return user properties
	public function read( $id )
	{
		$id = $this->_id( $id );
		if ( isset( $this->_data->{$id} ) )
		{
			$user = $this->_data->{$id};
			$user->id = $id;
			return $user;
		}
		return null;
	}

	// Update or create a user
	public function write( $user )
	{
		// If user has no ID, it has to be created.
		$id = $user->id();
		if ( !isset( $id ) )
		{
			$id = time();
			while ( isset( $this->_data->{$id} ) )
			{
				$id++;
			}
		}

		$this->_data->{$id} = (object)array(
			'username' => $user->username(),
			'password' => $user->password(),
			'data'     => $user->data() );

		return $this->_save();
	}

	// Delete a user
	public function delete( $id )
	{
		$id = $this->_id( $id );
		unset( $this->_data->{$id} );
		return $this->_save();
	}

	// Check if a user exists
	public function exists( $id )
	{
		$id = $this->_id( $id );
		return isset( $this->_data->{$id} );
	}

	// Check for a login username/password
	public function check( $id, $password )
	{
		$id = $this->_id( $id );
		return isset( $this->_data->{$id} ) and $this->_data->{$id}->password == $password;
	}

	// If ID is string, it is a username, convert it to an ID
	private function _id( $id )
	{
		if ( !is_int( $id ) )
		{
			foreach ( $this->_data as $key => $user )
			{
				if ( $user->username == $id )
				{
					return $key;
				}
			}
			return null;
		}
		return $id;
	}

	// Update the database
	private function _save()
	{
		return file_put_contents( self::$_file, json_encode( $this->_data ) );
	}
}

