<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Lang

	Locale handler.

	Author:
		Charly Lersteau

	Date:
		2013-10-16

	Example:
		>	// Simple translation
		>	Lang::define( 'en-US', 'test', array( 'hello' => 'Hello, $1 !' ) );
		>
		>	// Complex translation
		>	$func = Lang::func(
		>		'$n',
		>		'return ( $n == 1 ) ? "1 thing" : "{$n} things";'
		>	);
		>	Lang::define( 'en-US', 'test', array( 'func' => $func ) );
		>
		>	Lang::current( 'en-US' );
		>
		>	// Output: "Hello, world !" with standard syntax
		>	echo Lang::tr( 'test.hello', 'world' );
		>
		>	// Output: "5 things" with short syntax
		>	echo __( 'test.func', 5 );
*/
class Lang
{
	static protected $_data      = array();
	static protected $_current   = null;
	static protected $_separator = '.';

	/**
		Method: tr

		Retrieves a set of locale properties for the current language.

		Parameters:
			$key - (string) The member you wish to retrieve.
			... - (optional) (mixed) Arguments to apply.

		Returns:
			(mixed)

		Example:
			>	Lang::define( 'en-US', 'test', array( 'hello' => 'Hello, $1 !' ) );
			>	Lang::current( 'en-US' );
			>
			>	// Complete syntax
			>	echo Lang::tr( 'test.hello', 'world' );
			>
			>	// Short syntax
			>	echo __( 'test.hello', 'world' );
			>
			>	// Output: "Hello, world !"
	*/
	// We choose 'tr' instead of 'get' to not be confused with 'current'.
	static public function tr()
	{
		$arguments = func_get_args();
		$key   = array_shift( $arguments );
		$lang  = self::current();
		$value = null;

		if ( isset( self::$_data[ $lang ] ) )
		{
			$value = self::_get(
				self::$_data[ $lang ],
				strtok( $key, self::$_separator ),
				null
			);
		}

		if ( $value instanceof LangFunction )
		{
			return $value->__invoke( $arguments );
		}
		else if ( isset( $value ) )
		{
			$replace = array();
			foreach ( $arguments as $k => $v )
			{
				$replace[ '$'.($k+1) ] = $v;
			}
			return strtr( $value, $replace );
		}
		return $key;
	}

	/**
		Method: define

		Defines properties for a given set in a given language.

		Parameters:
			$lang - (string) The locale name.
			$key - (string) The set name.
			$value - (mixed) The value.

		Example:
			>	$func = Lang::func(
			>		'$n',
			>		'return ( $n == 1 ) ? "1 thing" : "{$n} things";'
			>	);
			>
			>	Lang::define( 'en-US', 'test', array( 'func' => $func ) );
			>
			>	echo __( 'test.func', 5 );
			>	// Output: "5 things"

	*/
	// We choose 'define' instead of 'set' to not be confused with 'current'.
	static public function define( $lang, $key, $value = null )
	{
		isset( $lang ) or $lang = self::current();

		if ( isset( $value ) )
		{
			self::_set(
				self::$_data,
				strtok( $lang.self::$_separator.$key, self::$_separator ),
				$value
			);
		}
		else foreach ( $key as $s => $v )
		{
			self::define( $lang, $s, $v );
		}
	}

	/**
		Method: current

		Get or set the current locale name.

		Parameters:
			$name - (optional) (string) The locale name.

		Returns:
			(string) The current locale name.
	*/
	static public function current( $name = null )
	{
		// setlocale() is not safe and not per-thread
		isset( $name ) and self::$_current = $name;
		return self::$_current;
	}

	/**
		Method: func

		Create a callable translation function.

		Parameters:
			$arguments - (string) The function arguments.
			$code - (string) The function code.

		Returns:
			(LangFunction)
	*/
	static public function func( $arguments, $code )
	{
		return new LangFunction( $arguments, $code );
	}

	/**
		Method: negotiate

		Negotiate clients preferred language.

		Parameters:
			$accepted - (string|array) Accept-Language header
				or array containing language-qvalue pairs.
			$supported - (optional) (array) Supported languages.

		Returns:
			(string) The negotiated language or null if none match.
	*/
	static public function negotiate( $accepted = null, $supported = null )
	{
		// Convert Accept-Language header to array.
		is_array( $accepted ) or $accepted = self::parseAcceptLanguage( $accepted );

		// Take defined languages.
		isset( $supported ) or $supported = array_keys( self::$_data );

		return self::lookup( $supported, $accepted );
	}

	/**
		Method: match

		Checks if a language tag filter matches with locale.

		Parameters:
			$langtag - (string) The language tag to check.
			$locale - (string) The language range to check against.
			$asFloat - (bool) If true, return a float between 0 and 1.

		Returns:
			(bool|float)

		Example:
			>	echo Lang::match( 'de-DEVA', 'de-DE' ) ? 'Matches' : 'Does not match';
			>	echo '; ';
			>	echo Lang::match( 'de-DE-1996', 'de-DE' ) ? 'Matches' : 'Does not match';
			>	// Output: "Does not match; Matches"
	*/
	static public function match( $langtag, $locale, $asFloat = false )
	{
		$langtag = strtolower( self::_normalize( $langtag ) );
		$locale  = strtolower( self::_normalize( $locale ) );

		if ( $langtag != $locale )
		{
			$langtag = self::parseLocale( $langtag );
			$locale  = self::parseLocale( $locale );
			$parts   = array_intersect( self::$_parts, array_keys( $locale ) );
			$count   = count( $parts );
			$i       = 0.;

			foreach ( $parts as $part )
			{
				$langtagPart = isset( $langtag[ $part ] ) ? $langtag[ $part ] : null;
				$localePart  = isset( $locale [ $part ] ) ? $locale [ $part ] : null;

				if ( $localePart != '*' and $localePart != $langtagPart )
				{
					return $asFloat ? floatval( $i / $count ) : false;
				}
				$i++;
			}
		}
		return $asFloat ? 1. : true;
	}

	/**
		Method: lookup

		Searches the language tag list for the best match to the language.

		Parameters:
			$supported - (array) An array containing a list of language tags.
			$accepted - (string|array) The locale to use as the language range
				or array containing language-qvalue pairs.
			$default - (optional) (string) The locale to use if no match is found.

		Returns:
			(string) The closest matching language tag or default value.
	*/
	static public function lookup( $supported, $accepted, $default = null )
	{
		is_array( $accepted ) or $accepted = array( $accepted => 1. );

		$count = count( $accepted );
		$match = array();

		foreach ( $supported as $langtag )
		{
			$match[ $langtag ] = 0.;

			foreach ( $accepted as $locale => $i )
			{
				// If 'de-DE' and 'de-DE-1996' supported and 'de-DE' accepted:
				// ('de-DE', 'de-DE') = 1. ; ('de-DE-1996', 'de-DE') = 1.
				// We make the test in two directions to not accidentally
				// choose 'de-DE-1996'.
				$score = self::match( $langtag, $locale, true )
					* self::match( $locale, $langtag, true ) * $i;

				$match[ $langtag ] = max( $score, $match[ $langtag ] );
			}
		}

		arsort( $match );
		return reset( $match ) ? key( $match ) : $default;
	}

	/**
		Method: parseLocale

		Returns a key-value array of locale ID subtag elements.

		Parameters:
			$locale - (string) The locale ID.

		Returns:
			(array)

		Example:
			> 'zh-gan-hak-Hans-CN-1901-rozaj-2abc-t-fonipa-u-islamcal-myext-testing-x-private-testing'
			> Array
			> (
			>     [langtag] => zh-gan-hak-Hans-CN-1901-rozaj-2abc-t-fonipa-u-islamcal-myext-testing-x-private-testing
			>     [language] => zh-gan-hak
			>     [extlang] => gan-hak
			>     [script] => Hans
			>     [region] => CN
			>     [variant] => 1901-rozaj-2abc
			>     [extension] => t-fonipa-u-islamcal-myext-testing
			>     [privateuse] => x-private-testing
			> )
	*/
	static public function parseLocale( $locale )
	{
		$locale = self::_normalize( $locale );
		if ( preg_match( self::BCP47, $locale, $matches ) )
		{
			// preg_match returns an array with integer indices, we delete them.
			$keys    = array_filter( array_keys( $matches ), 'is_string' );
			$matches = array_intersect_key( $matches, array_flip( $keys ) );
			return $matches;
		}
		return null;
	}

	/**
		Method: parseAcceptLanguage

		Parse an Accept-Language header and return sorted language-qvalue pairs.

		Parameters:
			$accepted - (optional) (string) An Accept-Language header.

		Returns:
			(array)
	*/
	static public function parseAcceptLanguage( $accept = null )
	{
		isset( $accept ) or $accept = $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ];

		preg_match_all(
			self::RFC2616_sec14_4,
			$accept,
			$matches,
			PREG_SET_ORDER
		);

		$parsed = array();
		$qvalue = 1.0;

		foreach ( $matches as $match )
		{
			$parsed[ $match[ 1 ] ] = $qvalue;
			empty( $match[ 2 ] ) or $qvalue = floatval( $match[ 2 ] );
		}

		// Order by quality.
		arsort( $parsed );
		return $parsed;
	}

	/**
		Method: separator

		Set the separator for multidimensional key names.

		Parameters:
			$separator - (string) The separator.
	*/
	static public function separator( $separator = null )
	{
		!isset( $separator ) or self::$_separator = $separator;
		return self::$_separator;
	}

	// Normalize
	static protected function _normalize( $locale )
	{
		return strtr( $locale, '_', '-' );
	}

	// Recursive get
	static protected function _get( &$data, $tok, $default )
	{
		if ( $tok !== false )
		{
			return isset( $data[ $tok ] )
				? self::_get( $data[ $tok ], strtok( self::$_separator ), $default )
				: $default;
		}
		return $data;
	}

	// Recursive set
	static protected function _set( &$data, $tok, $value )
	{
		if ( $tok !== false )
		{
			isset( $data ) or $data = array();
			self::_set( $data[ $tok ], strtok( self::$_separator ), $value );
		}
		else
		{
			$data = $value;
		}
	}

	// Accept-Language parser.
	const RFC2616_sec14_4 =
		"/((?:[[:alpha:]]{1,8})(?:-(?:[[:alpha:]|-]{1,8}))?)
			(?:\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(?:,|$)/ix";

	// Language tag parser.
	const BCP47 = '`^(?:
		(?P<langtag>
				# langtag =
				# language
				# ["-" script]
				# ["-" region]
				# *("-" variant)
				# *("-" extension)
				# ["-" privateuse]
			(?:
				(?P<language>
					[[:alpha:]]{2,3}                        #   2*3ALPHA ["-" extlang]
					(?:-
						(?P<extlang>
							[[:alpha:]]{3}              #     3ALPHA
							(?:-[[:alpha:]]{3}){0,2}    #     *2("-" 3ALPHA)
						)
					)?
					| [[:alpha:]]{4}                        # / 4ALPHA
					| [[:alpha:]]{5,8}                      # / 5*8ALPHA 
				)
				(?:-                               # script = 4ALPHA  ; ISO 15924 code
					(?P<script> [[:alpha:]]{4} )
				)?
				(?:-
					(?P<region>
						  [[:alpha:]]{2}    #   2ALPHA  ; ISO 3166-1 code
						| [[:digit:]]{3}    # / 3DIGIT  ; UN M.49 code
					)
				)?
				(?:-                 # variant = 5*8alphanum / (DIGIT 3alphanum)
					(?P<variant>
						     (?: [[:alnum:]]{5,8} | [[:digit:]][[:alnum:]]{3} )
						(?: -(?: [[:alnum:]]{5,8} | [[:digit:]][[:alnum:]]{3} ) )*
					)
				)?
				(?:-                 # extension = singleton 1*("-" (2*8alphanum))
					(?P<extension>
						     [0-9A-WY-Za-wy-z](?:-[[:alnum:]]{2,8})+
						(?: -[0-9A-WY-Za-wy-z](?:-[[:alnum:]]{2,8})+ )*
					)
				)?
			)?
			(?:
				(?:(?<=^)|-)         # privateuse = "x" 1*("-" (1*8alphanum))
				(?P<privateuse> [x] (?:-[[:alnum:]]{1,8})+ )
			)?
		)
		| (?P<grandfathered>
			(?P<irregular>
				  en-GB-oed     # irregular tags do not match
				| i-ami         # the "langtag" production and
				| i-bnn         # would not otherwise be
				| i-default     # considered "well-formed"
				| i-enochian    # These tags are all valid,
				| i-hak         # but most are deprecated
				| i-klingon     # in favor of more modern
				| i-lux         # subtags or subtag
				| i-mingo       # combination
				| i-navajo
				| i-pwn
				| i-tao
				| i-tay
				| i-tsu
				| sgn-BE-FR
				| sgn-BE-NL
				| sgn-CH-DE
			)
			| (?P<regular>
				  art-lojban     # these tags match the "langtag"
				| cel-gaulish    # production, but their subtags
				| no-bok         # are not extended language
				| no-nyn         # or variant subtags: their meaning
				| zh-guoyu       # is defined by their registration
				| zh-hakka       # and all of these are deprecated
				| zh-min         # in favor of a more modern
				| zh-min-nan     # subtag or sequence of subtags
				| zh-xiang
			)
		))$`x';

	static protected $_parts =
		array(
			'language',
			'extlang',
			'script',
			'region',
			'variant',
			'extension',
			'privateuse'
		);
}

/**
	Function: __

	Alias for <Lang::tr>.
*/
function __()
{
	$arguments = func_get_args();
	return call_user_func_array( array( 'Lang', 'tr' ), $arguments );
}

// Class to wrap a function.
class LangFunction
{
	protected $_function;

	public function __construct( $arguments, $code )
	{
		$this->_function = create_function( $arguments, $code );
	}

	public function __invoke( $arguments )
	{
		return call_user_func_array( $this->_function, $arguments );
	}
}
