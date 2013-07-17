<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Browser

	User-agent detection functions.

	Author:
		Charly Lersteau

	Date:
		2012-04-12

	Properties:
		- activexcontrols
		- alpha
		- aolversion
		- backgroundsounds
		- beta
		- browser
		- browser_name_pattern
		- browser_name_regex
		- cookies
		- crawler
		- cssversion
		- frames
		- iframes
		- isbanned
		- ismobiledevice
		- issyndicationreader
		- javaapplets
		- javascript
		- majorver
		- minorver
		- parent
		- platform
		- tables
		- vbscript
		- version
		- win16
		- win32
		- win64
*/
class Browser
{
	static protected $_file = null; // Path to browscap.ini
	static protected $_data = null; // Cached browser info

	/**
		Method: get

		Get a browser property or an array of properties.

		Parameters:
			$key - (optional) (string) The property name.
			$default - (optional) (mixed) A default value (default: null)

		Returns:
			(mixed)
	*/
	static public function get( $key = null, $default = null )
	{
		self::initialize();
		if ( isset( $key ) )
		{
			return isset( self::$_data[ $key ] ) ? self::$_data[ $key ] : $default;
		}
		return self::$_data;
	}

	/**
		Method: config

		Set the path of 'browscap.ini'.

		Parameters:
			$file - (string) The file name.

		Returns:
			(string)
	*/
	static public function config( $file = null )
	{
		!isset( $file ) or self::$_file = $file;
		return self::$_file;
	}

	// Initialize browser info.
	static public function initialize()
	{
		if ( !isset( self::$_data ) )
		{
			// Try to make the best choice.
			if ( !file_exists( ini_get( 'browscap' ) ) and !isset( self::$_file ) )
			{
				// If browscap.ini does not exist in config file php.ini:
				// Try to search for browscap.ini in the root directory
				self::$_file = 'browscap.ini';
				// Try to search for browscap.ini in this directory
				file_exists( self::$_file ) or self::$_file = dirname( __FILE__ ).'/browscap.ini';
				// Finally try to use get_browser() even if it is risky
				file_exists( self::$_file ) or self::$_file = null;
			}
			self::_update();
		}
	}

	// Get browser info.
	static protected function _update()
	{
		// No ini file to read => use get_browser().
		if ( !isset( self::$_file ) or !file_exists( self::$_file ) )
		{
			self::$_data = get_browser( null, true );
			return;
		}

		// Need RAW scanner to avoid warnings.
		if ( defined( 'INI_SCANNER_RAW' ) )
		{
			$ini = parse_ini_file( self::$_file, true, INI_SCANNER_RAW );
		}
		else
		{
			$ini = parse_ini_file( self::$_file, true );
		}

		// We want to find the pattern which has minimum characters replacements.
		$userAgent = $_SERVER[ 'HTTP_USER_AGENT' ];
		$bestCount = null;
		$browser   = null;

		foreach ( $ini as $pattern => &$properties )
		{
			// get_browser() use lower case keys and it is very useful.
			$properties = array_change_key_case( $properties, CASE_LOWER );

			// $count is the current number of character replacements.
			$count = self::_match( $pattern, $userAgent );

			if ( $count !== false and ( !isset( $bestCount ) or $count < $bestCount ) )
			{
				$bestCount = $count;
				$browser   = $pattern;
			}
		}

		// These values are added by get_browser(), can be useful.
		self::$_data = array(
			'browser_name_regex'   => self::_regex( $browser ),
			'browser_name_pattern' => $browser
		);

		// Parent categories have some needed properties.
		while ( isset( $ini[ $browser ][ 'parent' ] ) )
		{
			self::$_data = array_merge( $ini[ $browser ], self::$_data );
			$browser     = $ini[ $browser ][ 'parent' ];
		}
		self::$_data = array_merge( $ini[ $browser ], self::$_data );

		// Need to convert "123" to integer 123 and "true" to boolean true.
		array_walk( self::$_data, array( 'self', '_settype' ) );
	}

	// Make a regex with a pattern and a optional delimiter.
	static protected function _regex( $pattern, $d = null )
	{
		$replace = array( '\*' => '(.*)', '\?' => '(.)' );
		$quote   = preg_quote( $pattern, $d );
		return $d.'^'.strtr( $quote, $replace ).'$'.$d;
	}

	// Returns the number of characters replaced if it matches, else false.
	static protected function _match( $pattern, $userAgent )
	{
		$regex = self::_regex( $pattern, '%' ).'i';
		if ( preg_match( $regex, $userAgent, $matches ) )
		{
			// Count the number of characters replaced.
			$count = 0;
			foreach ( $matches as $match )
			{
				$count += strlen( $match );
			}
			return $count;
		}
		return false;
	}

	// Affect the good type to a value.
	static protected function _settype( &$value )
	{
		if ( ctype_digit( $value ) )
		{
			$value = intval( $value );
		}
		else if ( is_numeric( $value ) )
		{
			$value = floatval( $value );
		}
		else switch ( strtolower( $value ) )
		{
			case 'true':  $value = true;  break;
			case 'false': $value = false; break;
		}
	}
}
