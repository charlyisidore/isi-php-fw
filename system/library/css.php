<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: CSS

	A class to manage CSS stylesheets.

	Author:
		Charly LERSTEAU

	Date:
		2013-05-05
*/
class CSS
{
	// Regex patterns
	const COMMENT_PATTERN  = '@/\*[^*]*\*+([^/][^*]*\*+)*/@';
	const BLOCK_PATTERN    = '/([^{;]+)\s*\{\s*((?:[^{}]*|(?R))+)\s*\}/xm';
	const RULE_PATTERN     = '/([^:;]+)\s*\:\s*([^;]+)\s*;/m';
	const FUNCTION_PATTERN = '/([A-Za-z_-][A-Za-z0-9_-]*)\s*\(\s*((?:[^()]*|(?R))+)\s*\)/m';

	/**
		Method: compile

		Clean comments, spaces, add vendor prefixes and sort a CSS stylesheet.

		Parameters:
			$content - (string) The CSS file content.
			$minify - (bool) If true, try to compress the stylesheet.

		Returns:
			(string) A compiled CSS stylesheet string.
	*/
	static public function compile( $content, $minify = true )
	{
		// Parse the stylesheet and remove comments
		$css = self::decode( $content );

		// Remove superficial spaces
		self::clean( $css );

		// Replace variables names by their value
		self::vars( $css );

		// Add prefixes for some properties
		self::prefix( $css );

		// Recursively sort properties (not selectors)
		foreach ( $css as &$block )
		{
			self::sort( $block );
		}

		// Compress the stylesheet if requested
		return $minify ? self::minify( $css, false ) : self::encode( $css );
	}

	/**
		Method: tidy

		Clean comments, spaces and sort a CSS stylesheet. Keep unprocessed.

		Parameters:
			$content - (string) The CSS file content.

		Returns:
			(string) A compiled CSS stylesheet string.
	*/
	static public function tidy( $content )
	{
		// Parse the stylesheet and remove comments
		$css = self::decode( $content );

		// Remove unuseful spaces
		self::clean( $css );

		// Recursively sort properties (not selectors)
		foreach ( $css as &$block )
		{
			self::sort( $block );
		}

		// Return the stylesheet
		return self::encode( $css );
	}

	/**
		Method: decode

		Parse a CSS stylesheet string.

		Parameters:
			$content - (string) The CSS file content.

		Returns:
			(array) A parsed CSS stylesheet array.
	*/
	static public function decode( $content )
	{
		$css = array();

		// Remove new lines and unuseful spaces
		$content = str_replace( array("\n", "\r"), '', $content );
		$content = preg_replace( '/\s+/', ' ', $content );
		$content = preg_replace( '/\s*([,>+=[\]]|[~|^$*]=)\s*/', '$1', $content );

		// Remove comments
		$content = preg_replace( self::COMMENT_PATTERN, '', $content );

		// Recursively decode blocks
		self::decode_block( $css, $content );
		return $css;
	}

	/**
		Method: encode

		Convert a CSS stylesheet array to a string.

		Parameters:
			$css - (array) The CSS array.

		Returns:
			(string) A CSS stylesheet string.
	*/
	static public function encode( $css, $s = ': ', $n = "\n", $t = "\t", $depth = 0, $parent = null )
	{
		$content = '';

		// Indentation
		$tt = str_repeat( $t, $depth );

		foreach ( $css as $key => $value )
		{
			// Nested block
			if ( is_array( $value ) )
			{
				$key = str_replace( ',', ','.$n.$tt, $key );

				// Property which occurs multiple times
				if ( isset( $value[0] ) )
				{
					$content .= self::encode( $value, $s, $n, $t, $depth, $key );
				}
				else
				{
					$content .=
						$tt.$key.$n
						.$tt.'{'.$n
						.self::encode( $value, $s, $n, $t, $depth+1, $key )
						.$tt.'}'.$n;
				}
			}
			// Key-value pair with key occuring multiple times
			else if ( is_numeric( $key ) )
			{
				$content .= $tt.$parent.$s.$value.';'.$n;
			}
			// Unique key-value pair
			else
			{
				$content .= $tt.$key.$s.$value.';'.$n;
			}
		}
		return $content;
	}

	/**
		Method: minify

		Convert a CSS stylesheet array to a minified string.

		Parameters:
			$css - (array) The CSS array.

		Returns:
			(string) A CSS stylesheet string minimized.
	*/
	static public function minify( $css, $clean = true )
	{
		!$clean or self::clean( $css );
		return self::encode( $css, ':', '', '' );
	}

	/**
		Method: clean

		Remove superficial spaces in properties.

		Parameters:
			$css - (array) The CSS array.
	*/
	static public function clean( &$css )
	{
		foreach ( $css as $key => &$value )
		{
			if ( is_array( $value ) )
			{
				self::clean( $value );
			}
			else
			{
				self::clean_value( $value );
			}
		}
	}

	/**
		Method: sort

		Sort CSS selectors and properties.

		Parameters:
			$css - (array) The CSS array.
	*/
	static public function sort( &$css )
	{
		if ( !is_array( $css ) ) return;

		uksort( $css, array( 'CSS', 'ksort' ) );

		foreach ( $css as &$c )
		{
			self::sort( $c );
		}
	}

	/**
		Method: prefix

		Add vendor prefixes.

		Parameters:
			$css - (array) The CSS array.
	*/
	static public function prefix( &$css )
	{
		foreach ( $css as $key => &$c )
		{
			if ( is_array( $c ) )
			{
				self::prefix( $c );
			}
			else
			{
				// Prefix function names
				if ( preg_match( self::FUNCTION_PATTERN, $c, $f ) )
				{
					$fn = $f[1];
					if ( isset( self::$_prefix[$fn] ) )
					{
						$value = $css[$key];
						$css[$key] = array( $value );

						foreach ( self::$_prefix[$fn] as $prefix )
						{
							$css[$key][] = str_replace( $fn, '-'.$prefix.'-'.$fn, $value );
						}
					}
				}

				// Prefix property names
				if ( isset( self::$_prefix[$key] ) )
				{
					foreach ( self::$_prefix[$key] as $prefix )
					{
						if ( !isset( $css['-'.$prefix.'-'.$key] ) )
						{
							$css['-'.$prefix.'-'.$key] = $c;
						}
					}
				}
			}
		}
	}

	/**
		Method: unprefix

		Remove vendor prefixes.

		Parameters:
			$css - (array) The CSS array.
	*/
	static public function unprefix( &$css )
	{
		foreach ( $css as $key => &$c )
		{
			if ( $key[0] == '-' )
			{
				unset( $css[$key] );
			}
			else if ( is_array( $c ) )
			{
				self::unprefix( $c );
			}
		}
	}

	/**
		Method: vars

		Replace variables by their value.

		Parameters:
			$css - (array) The CSS array.
	*/
	static public function vars( &$css )
	{
		$vars = array();

		// Retrieve variable list
		foreach ( $css as $key => $value )
		{
			if ( $key[0] == '$' )
			{
				$vars[$key] = $value;
				unset( $css[$key] );
			}
		}

		// Replace variables
		self::replace( $css, $vars );
	}

	// Decode nested blocks
	static protected function decode_block( &$css, $content )
	{
		preg_match_all( self::BLOCK_PATTERN, $content, $blocks, PREG_SET_ORDER );

		// Treat nested blocks
		foreach ( $blocks as $block )
		{
			$selector = trim( $block[1] );
			$rules    = trim( $block[2] );

			// If a selector occurs multiple times
			isset( $css[$selector] ) or $css[$selector] = array();

			self::decode_block( $css[$selector], $rules );
		}

		// Treat rules inside this block
		self::decode_rule( $css, $content );
	}

	// Decode key-value pairs
	static protected function decode_rule( &$css, $content )
	{
		// Remove blocks
		$content = preg_replace( self::BLOCK_PATTERN, '', $content );

		preg_match_all( self::RULE_PATTERN, $content, $rules, PREG_SET_ORDER );

		foreach ( $rules as $property )
		{
			$key   = trim( $property[1] );
			$value = trim( $property[2] );

			// Try to put the property in an array if it occurs multiple times.
			if ( !isset( $css[$key] ) )
			{
				$css[$key] = $value;
			}
			else if ( is_array( $css[$key] ) and !in_array( $value, $css[$key] ) )
			{
				$css[$key][] = $value;
			}
			else if ( $css[$key] != $value )
			{
				$css[$key] = array( $css[$key], $value );
			}
		}
	}

	// Compares keys to sort them
	static protected function ksort( $a, $b )
	{
		$ia = array_search( $a, self::$_sort );
		$ib = array_search( $b, self::$_sort );

		if ( $ia !== false and $ib !== false )
		{
			if ( $ia < $ib ) return -1;
			else if ( $ia > $ib ) return 1;
			else return 0;
		}
		else if ( $ia !== false )
		{
			return -1;
		}
		else if ( $ib !== false )
		{
			return 1;
		}
		return strcmp( $a, $b );
	}

	// Recursively replace variables by their values
	static protected function replace( &$css, $vars )
	{
		foreach ( $css as $key => &$value )
		{
			if ( is_array( $value ) )
			{
				self::replace( $value, $vars );
			}
			else foreach( $vars as $n => $v )
			{
				$n = preg_quote( $n );
				$value = preg_replace( '/'.$n.'([^A-Za-z0-9_-]|$)/', $v.'$1', $value );
			}
		}
	}

	// Recursively remove superficial spaces in one function
	static protected function clean_value( &$value )
	{
		preg_match_all( self::FUNCTION_PATTERN, $value, $func, PREG_SET_ORDER );

		foreach ( $func as $f )
		{
			$fname = trim( $f[1] );
			$fargs = trim( $f[2] );

			// Recursive inspection
			self::clean_value( $fargs );

			$value = str_replace( $f[0], $fname.'('.$fargs.')', $value );
		}
	}

	// Key ordering
	static protected $_sort = array(
		'position',
		'top',
		'right',
		'bottom',
		'left',
		'z-index',
		'display',
		'visibility',
		'flex-direction',
		'flex-order',
		'flex-pack',
		'float',
		'clear',
		'flex-align',
		'overflow',
		'overflow-x',
		'overflow-y',
		'clip',
		'box-sizing',
		'margin',
		'margin-top',
		'margin-right',
		'margin-bottom',
		'margin-left',
		'padding',
		'padding-top',
		'padding-right',
		'padding-bottom',
		'padding-left',
		'min-width',
		'min-height',
		'max-width',
		'max-height',
		'width',
		'height',
		'outline',
		'outline-width',
		'outline-style',
		'outline-color',
		'outline-offset',
		'border',
		'border-spacing',
		'border-collapse',
		'border-width',
		'border-style',
		'border-color',
		'border-top',
		'border-top-width',
		'border-top-style',
		'border-top-color',
		'border-right',
		'border-right-width',
		'border-right-style',
		'border-right-color',
		'border-bottom',
		'border-bottom-width',
		'border-bottom-style',
		'border-bottom-color',
		'border-left',
		'border-left-width',
		'border-left-style',
		'border-left-color',
		'border-radius',
		'border-top-left-radius',
		'border-top-right-radius',
		'border-bottom-right-radius',
		'border-bottom-left-radius',
		'border-image',
		'border-image-source',
		'border-image-slice',
		'border-image-width',
		'border-image-outset',
		'border-image-repeat',
		'border-top-image',
		'border-right-image',
		'border-bottom-image',
		'border-left-image',
		'border-corner-image',
		'border-top-left-image',
		'border-top-right-image',
		'border-bottom-right-image',
		'border-bottom-left-image',
		'background',
		'background-color',
		'background-image',
		'background-attachment',
		'background-position',
		'background-position-x',
		'background-position-y',
		'background-clip',
		'background-origin',
		'background-size',
		'background-repeat',
		'box-decoration-break',
		'box-shadow',
		'color',
		'table-layout',
		'caption-side',
		'empty-cells',
		'list-style',
		'list-style-position',
		'list-style-type',
		'list-style-image',
		'quotes',
		'content',
		'counter-increment',
		'counter-reset',
		'vertical-align',
		'text-align',
		'text-align-last',
		'text-decoration',
		'text-emphasis',
		'text-emphasis-position',
		'text-emphasis-style',
		'text-emphasis-color',
		'text-indent',
		'text-justify',
		'text-outline',
		'text-transform',
		'text-wrap',
		'text-overflow',
		'text-overflow-ellipsis',
		'text-overflow-mode',
		'text-shadow',
		'white-space',
		'word-spacing',
		'word-wrap',
		'word-break',
		'tab-size',
		'hyphens',
		'letter-spacing',
		'font',
		'font-weight',
		'font-style',
		'font-variant',
		'font-size-adjust',
		'font-stretch',
		'font-size',
		'font-family',
		'src',
		'line-height',
		'opacity',
		'filter',
		'resize',
		'cursor',
		'nav-index',
		'nav-up',
		'nav-right',
		'nav-down',
		'nav-left',
		'transition',
		'transition-delay',
		'transition-timing-function',
		'transition-duration',
		'transition-property',
		'transform',
		'transform-origin',
		'animation',
		'animation-name',
		'animation-duration',
		'animation-play-state',
		'animation-timing-function',
		'animation-delay',
		'animation-iteration-count',
		'animation-direction',
		'pointer-events',
		'unicode-bidi',
		'direction',
		'columns',
		'column-span',
		'column-width',
		'column-count',
		'column-fill',
		'column-gap',
		'column-rule',
		'column-rule-width',
		'column-rule-style',
		'column-rule-color',
		'break-before',
		'break-inside',
		'break-after',
		'page-break-before',
		'page-break-inside',
		'page-break-after',
		'orphans',
		'widows',
		'zoom',
		'max-zoom',
		'min-zoom',
		'user-zoom',
		'orientation'
	);

	// Vendor prefixes
	static protected $_prefix = array(
		'border-radius'   => array( 'webkit' ),
		'box-shadow'      => array( 'webkit' ),
		'box-sizing'      => array( 'webkit', 'moz' ),
		'background-size' => array( 'webkit' ),
		'column-count'    => array( 'webkit', 'moz' ),
		'tab-size'        => array( 'moz', 'o' ),
		'transform'       => array( 'webkit', 'moz', 'ms', 'o' ),
		'perspective'     => array( 'webkit', 'moz', 'ms' ),
		'transition'      => array( 'webkit', 'moz', 'o' ),
		'animation'       => array( 'webkit', 'moz', 'o' ),
		'linear-gradient' => array( 'webkit', 'moz', 'o' ),
		'radial-gradient' => array( 'webkit', 'moz', 'ms', 'o' )
	);
}

