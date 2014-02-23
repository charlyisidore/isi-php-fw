<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Persona

	Authentication library using Persona.

	Author:
		Charly LERSTEAU

	Date:
		2014-02-23
*/
class Persona
{
	// Default verifier.
	static protected $_url = 'https://verifier.login.persona.org/verify';

	protected $_verifier  = null;
	protected $_assertion = null;
	protected $_status    = null;
	protected $_email     = null;
	protected $_audience  = null;
	protected $_expires   = null;
	protected $_issuer    = null;

	/**
		Constructor: Persona

		Parameters:
			$assertion - (string) An assertion.
			$verifier - (optional) (string) A verifier URL.
	*/
	public function __construct( $assertion = null, $verifier = null )
	{
		$this->_assertion = $assertion;
		$this->_verifier  = $verifier;
	}

	/**
		Method: factory

		Create a new Persona instance.

		Parameters:
			$assertion - (string) An assertion.
			$verifier - (optional) (string) A verifier URL.

		Returns:
			(self) A new Persona instance.
	*/
	static public function factory( $assertion = null, $verifier = null )
	{
		return new self( $assertion, $verifier );
	}

	/**
		Method: okay

		Return true if the authentication was a success, false otherwise.
	*/
	public function okay()
	{
		return $this->_status === 'okay';
	}

	/**
		Method: verifier

		Get or set the verifier URL.
	*/
	public function verifier( $verifier = null )
	{
		if ( isset( $verifier ) )
		{
			$this->_verifier = $verifier;
		}
		else
		{
			return isset( $this->_verifier )
				? $this->_verifier
				: self::$_url;
		}
		return $this;
	}

	/**
		Method: assertion

		Get or set the assertion.
	*/
	public function assertion( $assertion = null )
	{
		if ( isset( $assertion ) )
		{
			$this->_assertion = $assertion;
		}
		else
		{
			return $this->_assertion;
		}
		return $this;
	}

	/**
		Method: status

		Return the authentication status (may be "okay" or "failure").
	*/
	public function status()
	{
		return $this->_status;
	}

	/**
		Method: email

		The address contained in the assertion, for the intended person
		being logged in.
	*/
	public function email()
	{
		return $this->_email;
	}

	/**
		Method: audience

		The audience value contained in the assertion. Expected to be
		your own website URL.
	*/
	public function audience()
	{
		return $this->_audience;
	}

	/**
		Method: expires

		The date the assertion expires, expressed as the primitive value
		of a Date object: that is, the number of milliseconds since
		midnight 01 January, 1970 UTC.
	*/
	public function expires()
	{
		return $this->_expires;
	}

	/**
		Method: issuer

		The hostname of the identity provider that issued the assertion.
	*/
	public function issuer()
	{
		return $this->_issuer;
	}

	/*
		Method: verify

		Verify the assertion.

		Returns:
			(bool) true is success, false otherwise.
	*/
	public function verify( $client = array( 'self', '_post' ) )
	{
		$url  = $this->verifier();
		$data = array(
			'assertion' => $this->assertion(),
			'audience' => $this->_getaudience()
		);

		$data = http_build_query( $data );
		$text = call_user_func( $client, $url, $data );
		$json = json_decode( $text );

		$this->_status = strtolower( $json->status );

		if ( $this->okay() )
		{
			$this->_email    = $json->email;
			$this->_audience = $json->audience;
			$this->_expires  = $json->expires;
			$this->_issuer   = $json->issuer;
		}
		return $this->okay();
	}

	/*
		Method: initialize

		Set the default verifier.
	*/
	static public function initialize( $verifier )
	{
		self::$_url = $verifier;
	}

	// Generate audience URL.
	protected function _getaudience()
	{
		return ( ( isset( $_SERVER['HTTPS'] ) and $_SERVER['HTTPS'] === 'on' )
				? 'https'
				: 'http' )
			. '://' . $_SERVER['SERVER_NAME']
			. ':' . $_SERVER['SERVER_PORT'];
	}

	// HTTP client.
	protected function _post( $url, $data )
	{
		if ( extension_loaded( 'curl' ) )
		{
			$curl = curl_init();
			$params = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => $data
			);
			curl_setopt_array( $curl, $params );
			$result = curl_exec( $curl );
			curl_close( $curl );
		}
		else
		{
			$params = array(
				'http' => array(
					'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
					            "Content-Length: ".strlen( $data )."\r\n",
					'method' => 'POST',
					'content' => $data
				)
			);
			$context = stream_context_create( $params );
			$result  = file_get_contents( $url, false, $context );
		}
		return $result;
	}
}

