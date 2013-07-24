<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: ACL

	A simple Access Control List implementation using a tree of roles
	and a tree of resources.

	Author:
		Charly Lersteau

	Date:
		2013-07-24

	Example:
		>	$acl = ACL::instance();
		>	
		>	$acl->addRole( 'guest' )
		>	    ->addRole( 'member', 'guest' )
		>	    ->addRole( 'vip', 'member' )
		>	    ->addRole( 'admin', 'member' )
		>	    ->addRole( 'my_user', 'vip' );
		>	
		>	$acl->add( 'comment' )
		>	    ->add( 'comment/read', 'comment' )
		>	    ->add( 'comment/post', 'comment' )
		>	    ->add( 'comment/edit', 'comment' )
		>	    ->add( 'comment/edit/own', 'comment/edit' );
		>	
		>	$acl->allow( 'guest', 'comment/read' )
		>	    ->allow( 'member', 'comment/post' )
		>	    ->allow( 'admin', 'comment' )
		>	    ->allow( 'vip', 'comment/edit/own' )
		>	    ->deny( 'my_user', 'comment/post' );
		>	
		>	header( 'Content-Type: text/plain' );
		>	
		>	echo "guest:\n";
		>	echo "- read ", $acl->isAllowed( 'guest', 'comment/read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $acl->isAllowed( 'guest', 'comment/post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $acl->isAllowed( 'guest', 'comment/edit' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit own ", $acl->isAllowed( 'guest', 'comment/edit/own' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "member:\n";
		>	echo "- read ", $acl->isAllowed( 'member', 'comment/read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $acl->isAllowed( 'member', 'comment/post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $acl->isAllowed( 'member', 'comment/edit' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit own ", $acl->isAllowed( 'member', 'comment/edit/own' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "vip:\n";
		>	echo "- read ", $acl->isAllowed( 'vip', 'comment/read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $acl->isAllowed( 'vip', 'comment/post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $acl->isAllowed( 'vip', 'comment/edit' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit own ", $acl->isAllowed( 'vip', 'comment/edit/own' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "admin:\n";
		>	echo "- read ", $acl->isAllowed( 'admin', 'comment/read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $acl->isAllowed( 'admin', 'comment/post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $acl->isAllowed( 'admin', 'comment/edit' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit own ", $acl->isAllowed( 'admin', 'comment/edit/own' ) ? 'allowed' : 'denied', "\n";
		>	
		>	echo "my_user:\n";
		>	echo "- read ", $acl->isAllowed( 'my_user', 'comment/read' ) ? 'allowed' : 'denied', "\n";
		>	echo "- post ", $acl->isAllowed( 'my_user', 'comment/post' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit ", $acl->isAllowed( 'my_user', 'comment/edit' ) ? 'allowed' : 'denied', "\n";
		>	echo "- edit own ", $acl->isAllowed( 'my_user', 'comment/edit/own' ) ? 'allowed' : 'denied', "\n";
		>	
		>	// guest:
		>	// - read allowed
		>	// - post denied
		>	// - edit denied
		>	// - edit own denied
		>	// member:
		>	// - read allowed
		>	// - post allowed
		>	// - edit denied
		>	// - edit own denied
		>	// vip:
		>	// - read allowed
		>	// - post allowed
		>	// - edit denied
		>	// - edit own allowed
		>	// admin:
		>	// - read allowed
		>	// - post allowed
		>	// - edit allowed
		>	// - edit own allowed
		>	// my_user:
		>	// - read allowed
		>	// - post denied
		>	// - edit denied
		>	// - edit own allowed
*/
class ACL
{
	static protected $_instances = array();
	protected $_roles = array();
	protected $_resources = array();
	protected $_allow = array();
	protected $_deny = array();

	/**
		Method: instance

		Create or retrieve an ACL instance.

		Parameters:
			$name - (optional) (string) Instance name.
	*/
	static public function instance( $name = null )
	{
		isset( self::$_instances[ $name ] ) or self::$_instances[ $name ] = new self;
		return self::$_instances[ $name ];
	}

	/**
		Method: addRole

		Append a role.

		Parameters:
			$name - (string) Role name.
			$parent - (optional) (string) Parent of the role.
	*/
	public function addRole( $name, $parent = false )
	{
		return $this->_add( '_roles', $name, $parent );
	}

	/**
		Method: removeRole

		Delete a role.

		Parameters:
			$name - (string) Role name.
	*/
	public function removeRole( $name )
	{
		return $this->_remove( '_roles', $name );
	}

	/**
		Method: hasRole

		Check whether a specified role exists.

		Parameters:
			$name - (string) Role name.
	*/
	public function hasRole( $name )
	{
		return isset( $this->_roles[ $name ] );
	}

	/**
		Constructor: add

		Append a resource.

		Parameters:
			$name - (string) Resource name.
			$parent - (optional) (string) Parent of the resource.
	*/
	public function add( $name, $parent = false )
	{
		return $this->_add( '_resources', $name, $parent );
	}

	/**
		Method: remove

		Delete a resource.

		Parameters:
			$name - (string) Resource name.
	*/
	public function remove( $name )
	{
		return $this->_remove( '_resources', $name );
	}

	/**
		Method: has

		Check whether a specified resource exists.

		Parameters:
			$name - (string) Resource name.
	*/
	public function has( $name )
	{
		return isset( $this->_resources[ $name ] );
	}

	/**
		Method: allow

		Append an allow rule.
	*/
	public function allow( $role, $resource )
	{
		return $this->_write( '_allow', $role, $resource );
	}

	/**
		Method: deny

		Append a deny rule.
	*/
	public function deny( $role, $resource )
	{
		return $this->_write( '_deny', $role, $resource );
	}

	/**
		Method: removeAllow

		Delete an allow rule.
	*/
	public function removeAllow( $role, $resource )
	{
		return $this->_delete( '_allow', $role, $resource );
	}

	/**
		Method: removeDeny

		Delete a deny rule.
	*/
	public function removeDeny( $role, $resource )
	{
		return $this->_delete( '_deny', $role, $resource );
	}

	/**
		Method: isAllowed

		Return true if role is allowed to make specified action.
	*/
	public function isAllowed( $role, $resource )
	{
		$permissions = array(
			'_deny' => false,
			'_allow' => true
		);

		while ( !empty( $role ) )
		{
			$r = $resource;
			while ( !empty( $r ) )
			{
				// Rule #1: check for role-resource-specific deny rule
				// Rule #2: check for role-resource-specific allow rule
				foreach ( $permissions as $type => $result )
				{
					if ( isset( $this->{$type}[ $r ] ) and in_array( $role, $this->{$type}[ $r ] ) )
					{
						return $result;
					}
				}

				$r = $this->_resources[ $r ];
			}
			$role = $this->_roles[ $role ];
		}
		return false;
	}

	/**
		Method: import

		Import rules.

		Parameters:
			$data - (array|string) The data in array or JSON format.
	*/
	public function import( $data )
	{
		is_string( $data ) and $data = json_decode( $data, true );

		foreach ( array( 'roles', 'resources', 'allow', 'deny' ) as $p )
		{
			$this->{"_$p"} = $data[ $p ];
		}
	}

	/**
		Method: export

		Export rules in array format.
	*/
	public function export()
	{
		return array(
			'roles'     => $this->_roles,
			'resources' => $this->_resources,
			'allow'     => $this->_allow,
			'deny'      => $this->_deny
		);
	}

	/**
		Method: __toString

		Export rules in JSON format.
	*/
	public function __toString()
	{
		return json_encode( $this->export() );
	}

	// Generic add function
	public function _add( $array, $name, $parent )
	{
		if ( is_array( $name ) )
		{
			foreach ( $name as $n )
			{
				$this->_add( $array, $n, $parent );
			}
		}
		else
		{
			$this->{$array}[ $name ] = $parent;
		}
		return $this;
	}

	// Generic remove function
	public function _remove( $array, $name )
	{
		if ( is_array( $name ) )
		{
			foreach ( $name as $n )
			{
				$this->_remove( $array, $n );
			}
		}
		else
		{
			unset( $this->{$array}[ $name ] );
		}
		return $this;
	}

	// Write allow/deny rule
	public function _write( $permission, $role, $resource )
	{
		isset( $this->{$permission}[ $resource ] ) or $this->{$permission}[ $resource ] = array();
		$this->{$permission}[ $resource ][] = $role;
		return $this;
	}

	// Delete allow/deny rule
	public function _delete( $permission, $role, $resource )
	{
		$key = array_search( $role, $this->{$permission}[ $resource ] );
		if ( $key !== false )
		{
			unset( $this->{$permission}[ $resource ][ $key ] );
		}
		return $this;
	}
}
