<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Url

	Functions to parse URLs.

	Author:
		Charly Lersteau

	Date:
		2013-04-06

	Example:
		>	// http://www.site.com/base/index.php/to/my/page
		>	echo Url::page( '/to/my/page' );
		>
		>	// http://www.site.com/base/to/style.css
		>	echo Url::file( '/to/style.css' );
*/
class Url
{
	static protected $_rewrite = false;

	protected $_scheme   = null;
	protected $_host     = null;
	protected $_port     = null;
	protected $_user     = null;
	protected $_pass     = null;
	protected $_path     = null;
	protected $_query    = null;
	protected $_fragment = null;

	/**
		Constructor: Url

		Parameters:
			$url - (optional) (string) An URL to parse.
	*/
	public function __construct( $url = null )
	{
		if ( isset( $url ) )
		{
			$this->_scheme   = parse_url( $url, PHP_URL_SCHEME );
			$this->_host     = parse_url( $url, PHP_URL_HOST );
			$this->_port     = parse_url( $url, PHP_URL_PORT );
			$this->_user     = parse_url( $url, PHP_URL_USER );
			$this->_pass     = parse_url( $url, PHP_URL_PASS );
			$this->_path     = parse_url( $url, PHP_URL_PATH );
			$this->_query    = parse_url( $url, PHP_URL_QUERY );
			$this->_fragment = parse_url( $url, PHP_URL_FRAGMENT );
		}
	}

	/**
		Method: factory

		Create a new Url instance.

		Parameters:
			$url - (optional) (string) An URL to parse.

		Returns:
			(self) A new Url instance.
	*/
	static public function factory( $url = null )
	{
		return new self( $url );
	}

	/**
		Method: scheme

		Scheme value (http, ftp...).

		Parameters:
			$scheme - (string)
	*/
	public function scheme( $scheme = null )
	{
		return $this->_property( '_scheme', $scheme );
	}

	/**
		Method: host

		Host value (www.site.com).

		Parameters:
			$host - (string)
	*/
	public function host( $host = null )
	{
		return $this->_property( '_host', $host );
	}

	/**
		Method: port

		Port value.

		Parameters:
			$port - (int)
	*/
	public function port( $port = null )
	{
		return $this->_property( '_port', $port );
	}

	/**
		Method: user

		Authentication user value.

		Parameters:
			$user - (string)
	*/
	public function user( $user = null )
	{
		return $this->_property( '_user', $user );
	}

	/**
		Method: pass

		Authentication password value.

		Parameters:
			$pass - (string)
	*/
	public function pass( $pass = null )
	{
		return $this->_property( '_pass', $pass );
	}

	/**
		Method: path

		Path value (path/to/index.php).

		Parameters:
			$path - (string)
	*/
	public function path( $path = null )
	{
		return $this->_property( '_path', $path );
	}

	/**
		Method: query

		Query value (a=1&b=2).

		Parameters:
			$query - (string)
	*/
	public function query( $query = null )
	{
		return $this->_property( '_query', $query );
	}

	/**
		Method: fragment

		Fragment value.

		Parameters:
			$fragment - (string)
	*/
	public function fragment( $fragment = null )
	{
		return $this->_property( '_fragment', $fragment );
	}

	/**
		Method: __toString

		Build the URL.

		Returns:
			(string)
	*/
	public function __toString()
	{
		return  ( $this->scheme() ? $this->scheme().'://' : '' )
		      . ( $this->user()   ? $this->user().( $this->pass() ? ':'.$this->pass() : '' ).'@' : '' )
		      . ( $this->host()   ? $this->host() : '' )
		      . ( $this->port()   ? ':'.$this->port() : '' )
		      . ( $this->path()   ? $this->path() : '' )
		      . ( $this->query()  ? '?'.$this->query() : '' )
		      . ( $this->fragment() ? '#'.$this->fragment() : '' );
	}

	/**
		Method: current

		The URI which was given in order to access this page.

		Returns:
			(string)
	*/
	static public function current()
	{
		return $_SERVER[ 'REQUEST_URI' ];
	}

	/**
		Method: page

		Build an URL with a virtual path ('/foo/index.php/virtual/path').
		Removes trailing and double '/'.

		Parameters:
			$path - (string) The virtual path.
			$rewrite - (optional) (bool) Force rewrite option.

		Returns:
			(string)
	*/
	static public function page( $path, $rewrite = null )
	{
		isset( $rewrite ) or $rewrite = self::$_rewrite;
		return Url::base( !$rewrite ).'/'.preg_replace( '`/+`', '/', trim( $path, '/' ) );
	}

	/**
		Method: file

		Build an URL to a real file.

		Parameters:
			$path - (string) The path.

		Returns:
			(string)
	*/
	static public function file( $path )
	{
		return Url::base().'/'.ltrim( $path, '/' );
	}

	/**
		Method: base

		Client side base path.

		Parameters:
			$script - (optional) (bool) Add script file name (default: false).

		Returns:
			(string)

		Examples:
			>	'/path'
			in
			>	http://site.com/path/index.php

			>	''
			in
			>	http://site.com/index.php
	*/
	static public function base( $script = false )
	{
		return $script
			? $_SERVER[ 'SCRIPT_NAME' ]
			: rtrim( dirname( $_SERVER[ 'SCRIPT_NAME' ] ), '/' );
	}

	/**
		Method: redirect

		Redirect to a new path and exit.

		Parameters:
			$path - (string) The path.
			$status - (optional) (int) HTTP status.
	*/
	static public function redirect( $path, $status = null )
	{
		// If $path has a host, it is absolute.
		parse_url( $path, PHP_URL_HOST ) or $path = Url::build( $path );
		// Status: 301 (Moved Permanently), 302 (Found), 303 (See Other), 307 (Temporary Redirect).
		headers_sent() or header( "Location: {$path}", true, $status );
		exit;
	}

	/**
		Method: rewrite

		Determine if the Url builder has to add script file name on urls.
	*/
	static public function rewrite( $rewrite = null )
	{
		!isset( $rewrite ) or self::$_rewrite = $rewrite;
		return self::$_rewrite;
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
