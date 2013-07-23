<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: ACL

	A simple Access Control List implementation.

	Author:
		Charly Lersteau

	Date:
		2013-07-23

	Example:
		>	ACL::role( 'guest' );
		>	ACL::role( 'member', 'guest' );
		>	ACL::role( 'admin', 'member' );
		>	ACL::role( 'my_user', 'member' );
		>	
		>	$comment = ACL::resource( 'comment' );
		>	
		>	$comment->action( array( 'read', 'post', 'edit' ) );
		>	
		>	$comment->allow( 'guest', 'read' );
		>	$comment->allow( 'member', 'post' );
		>	$comment->allow( 'admin', 'edit' );
		>	
		>	header( 'Content-Type: text/plain' );
		>	
		>	echo "guest:\n";
		>	echo "- read ", $comment->isAllowed( 'guest', 'read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $comment->isAllowed( 'guest', 'post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $comment->isAllowed( 'guest', 'edit' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "member:\n";
		>	echo "- read ", $comment->isAllowed( 'member', 'read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $comment->isAllowed( 'member', 'post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $comment->isAllowed( 'member', 'edit' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "admin:\n";
		>	echo "- read ", $comment->isAllowed( 'admin', 'read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $comment->isAllowed( 'admin', 'post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $comment->isAllowed( 'admin', 'edit' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "my_user:\n";
		>	echo "- read ", $comment->isAllowed( 'my_user', 'read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $comment->isAllowed( 'my_user', 'post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $comment->isAllowed( 'my_user', 'edit' ) ? 'allowed' : 'denied', "\n";
		>	
		>	// guest:
		>	// - read allowed
		>	// - post denied
		>	// - edit denied
		>	// member:
		>	// - read allowed
		>	// - post allowed
		>	// - edit denied
		>	// admin:
		>	// - read allowed
		>	// - post allowed
		>	// - edit allowed
		>	// my_user: 
		>	// - read allowed
		>	// - post allowed
		>	// - edit denied

*/
class ACL
{
	static protected $_parent = array();
	static protected $_resources = array();
	protected $_rules = array();

	/**
		Method: role

		Append a role.

		Parameters:
			$name - (string) Role name.
			$parent - (optional) (string) Parent of the role.
	*/
	static public function role( $name, $parent = null )
	{
		if ( is_array( $name ) )
		{
			foreach ( $name as $role )
			{
				self::role( $role, $parent );
			}
		}
		else
		{
			self::$_parent[ $name ] = $parent;
		}
	}

	/**
		Constructor: resource

		Create or retrieve a resource ACL.

		Parameters:
			$name - (string) Resource name.
			$actions - (optional) (array) Available actions on the resource.
	*/
	static public function resource( $name, $actions = array() )
	{
		if ( !isset( self::$_resources[ $name ] ) )
		{
			self::$_resources[ $name ] = new ACL;
			self::$_resources[ $name ]->action( $actions );
		}
		return self::$_resources[ $name ];
	}

	/**
		Method: action

		Create an action.

		Parameters:
			$name - (string) Action name.
	*/
	public function action( $name )
	{
		if ( is_array( $name ) )
		{
			foreach ( $name as $n )
			{
				$this->action( $n );
			}
		}
		else
		{
			$this->_rules[ $name ] = array(
				true  => array(),
				false => array()
			);
		}
	}

	/**
		Method: allow

		Append an allow rule.
	*/
	public function allow( $role, $action )
	{
		$this->_write( $role, $action, true );
	}

	/**
		Method: deny

		Append a deny rule.
	*/
	public function deny( $role, $action )
	{
		$this->_write( $role, $action, false );
	}

	/**
		Method: isAllowed

		Return true if role is allowed to make specified action.
	*/
	public function isAllowed( $role, $action )
	{
		// Role-specific rule (test if denied, then test if allowed)
		foreach ( array( false, true ) as $perm )
		{
			if ( in_array( $role, $this->_rules[ $action ][ $perm ] ) )
			{
				return $perm;
			}
		}

		// If role-specific rule not found, search for ancestors, deny by default
		return !empty( self::$_parent[ $role ] )
			and $this->isAllowed( self::$_parent[ $role ], $action );
	}

	// Write one rule
	protected function _write( $role, $action, $perm )
	{
		if ( is_array( $action ) )
		{
			foreach ( $action as $p )
			{
				$this->_write( $role, $p, $perm );
			}
		}
		else
		{
			$this->_rules[ $action ][ $perm ][] = $role;
		}
	}

	// Protected constructor
	protected function __construct()
	{
	}
}
