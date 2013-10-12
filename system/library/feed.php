<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Feed

	A class to manage Atom/RSS feeds.
	Basic support of Atom 1.0 and RSS 2.0 formats.

	Author:
		Charly LERSTEAU

	Date:
		2013-10-12
*/
class Feed
{
	// Required
	protected $_id;
	protected $_title; // Required in RSS
	protected $_updated;

	// Recommended
	protected $_link; // Required in RSS

	// Optional
	protected $_author;
	protected $_category;
	protected $_contributor;
	protected $_generator;
	protected $_icon;
	protected $_logo;
	protected $_rights;
	protected $_subtitle; // Required in RSS (as description)

	// Entries
	protected $_entry = array();

	// Atom-RSS converter
	static protected $_toRSS = array(
		'id'       => 'guid',
		'title'    => 'title',
		'updated'  => 'lastBuildDate',
		'link'     => 'link',
		'author'   => 'author',
		'category' => 'category',
		'logo'     => 'image',
		'rights'   => 'copyright',
		'subtitle' => 'description'
	);

	/**
		Constructor: __construct

		Parameters:
			$content - (mixed) A XML content.
	*/
	public function __construct( $content = null )
	{
		// Empty feed
		if ( !isset( $content ) )
		{
			$content = new SimpleXMLElement( '<feed/>' );
		}

		// DOMDocument -> SimpleXMLElement
		if ( $content instanceof DOMNode )
		{
			$content = simplexml_import_dom( $content );
		}

		// string -> SimpleXMLElement
		if ( !( $content instanceof SimpleXMLElement ) )
		{
			$content = simplexml_load_string( $content );
		}

		// The root tag name says something about the input format
		$tag = strtolower( $content->getName() );

		switch ( $tag )
		{
			case 'rss': // RSS
				$this->_parseRSS( $content );
				break;

			case 'feed': // Atom
			default:
				$this->_parseAtom( $content );
				break;
		}
	}

	/**
		Method: factory

		Create a new Feed instance.

		Returns:
			(self) A new Feed instance.
	*/
	public static function factory( $content = null )
	{
		return new self( $content );
	}

	/**
		Method: id

		Get or set the ID of the feed.

		Parameters:
			$id - (string) The ID.
	*/
	public function id( $id = null )
	{
		return $this->_property( '_id', $id );
	}

	/**
		Method: title

		Get or set the title of the feed.

		Parameters:
			$title - (string) The title.
	*/
	public function title( $title = null )
	{
		return $this->_property( '_title', $title );
	}

	/**
		Method: updated

		Get or set the update date of the feed.

		Parameters:
			$updated - (string) The date.
	*/
	public function updated( $updated = null )
	{
		return $this->_property( '_updated', $updated );
	}

	/**
		Method: link

		Get or set the link of the feed.

		Parameters:
			$link - (string) The link.
	*/
	public function link( $link = null )
	{
		return $this->_property( '_link', $link );
	}

	/**
		Method: author

		Get or set the author of the feed.

		Parameters:
			$author - (string) The author.
	*/
	public function author( $author = null )
	{
		return $this->_property( '_author', $author );
	}

	/**
		Method: category

		Get or set the category of the feed.

		Parameters:
			$category - (string) The category.
	*/
	public function category( $category = null )
	{
		return $this->_property( '_category', $category );
	}

	/**
		Method: contributor

		Get or set the contributor of the feed.

		Parameters:
			$contributor - (string) The contributor.
	*/
	public function contributor( $contributor = null )
	{
		return $this->_property( '_contributor', $contributor );
	}

	/**
		Method: generator

		Get or set the generator of the feed.

		Parameters:
			$generator - (string) The generator.
	*/
	public function generator( $generator = null )
	{
		return $this->_property( '_generator', $generator );
	}

	/**
		Method: icon

		Get or set the icon of the feed.

		Parameters:
			$icon - (string) The icon.
	*/
	public function icon( $icon = null )
	{
		return $this->_property( '_icon', $icon );
	}

	/**
		Method: logo

		Get or set the logo of the feed.

		Parameters:
			$logo - (string) The logo.
	*/
	public function logo( $logo = null )
	{
		return $this->_property( '_logo', $logo );
	}

	/**
		Method: rights

		Get or set the rights of the feed.

		Parameters:
			$rights - (string) The rights.
	*/
	public function rights( $rights = null )
	{
		return $this->_property( '_rights', $rights );
	}

	/**
		Method: subtitle

		Get or set the subtitle of the feed.

		Parameters:
			$subtitle - (string) The subtitle.
	*/
	public function subtitle( $subtitle = null )
	{
		return $this->_property( '_subtitle', $subtitle );
	}

	/**
		Method: count

		Get the number of entries.
	*/
	public function count()
	{
		return count( $this->_entry[$index] );
	}

	/**
		Method: entry

		Get an entry.

		Parameters:
			$index - (int) The entry index.
	*/
	public function entry( $index = null )
	{
		return isset( $index ) ? $this->_entry[$index] : $this->_entry;
	}

	/**
		Method: addEntry

		Append an entry.
	*/
	public function addEntry()
	{
		$entry = new FeedEntry;
		$this->_entry[] = $entry;
		return $entry;
	}

	/**
		Method: removeEntry

		Delete an entry.

		Parameters:
			$index - (int) The entry index.
	*/
	public function removeEntry( $index )
	{
		unset( $this->_entry[$index] );
		return $this;
	}

	/**
		Method: asAtom

		Export as Atom.
	*/
	public function asAtom()
	{
		$xml = new SimpleXMLElement( '<feed xmlns="http://www.w3.org/2005/Atom"/>' );
		$xml->id      = $this->id();
		$xml->title   = $this->title();
		$xml->updated = $this->updated();

		$optional = array(
			'link',
			'author',
			'category',
			'contributor',
			'generator',
			'icon',
			'logo',
			'rights',
			'subtitle' );

		foreach ( $optional as $tag )
		{
			$value = $this->{$tag}();

			if ( isset( $value ) )
			{
				$xml->{$tag} = $value;
			}
		}

		foreach ( $this->_entry as $entry )
		{
			$item = $xml->addChild( 'entry' );
			$entry->_appendAtom( $item );
		}

		return $xml->asXML();
	}

	/**
		Method: asRSS

		Export as RSS.
	*/
	public function asRSS()
	{
		$xml = new SimpleXMLElement( '<rss version="2.0"><channel/></rss>' );
		$xml->channel->title       = $this->title();
		$xml->channel->description = $this->subtitle();
		$xml->channel->link        = $this->link();

		$optional = array(
			'id',
			'updated',
			'author',
			'category',
			'logo',
			'rights' );

		foreach ( $optional as $tag )
		{
			$value = $this->{$tag}();

			if ( isset( $value ) )
			{
				$xml->channel->{self::$_toRSS[$tag]} = $value;
			}
		}

		foreach ( $this->_entry as $entry )
		{
			$item = $xml->addChild( 'item' );
			$entry->_appendRSS( $item );
		}

		return $xml->asXML();
	}

	/**
		Method: __toString

		Export as Atom.
	*/
	public function __toString()
	{
		return $this->asAtom();
	}

	// Set a property.
	protected function _property( $name, $value, $default = null )
	{
		if ( isset( $value ) )
		{
			$this->{$name} = $value;
		}
		else
		{
			return isset( $this->{$name} ) ? $this->{$name} : $default;
		}
		return $this;
	}

	// Parse an Atom feed
	protected function _parseAtom( $xml )
	{
		foreach ( $xml->children() as $node )
		{
			$tag = strtolower( $node->getName() );

			if ( $tag === 'entry' )
			{
				$this->_entry = new FeedEntry( $node );
			}
			else if ( property_exists( $this, '_'.$tag ) )
			{
				$this->_property( '_'.$tag, (string)$node );
			}
		}
	}

	// Parse a RSS feed
	protected function _parseRSS( $xml )
	{
		$fromRSS = array_combine(
				array_map( 'strtolower', array_values( self::$_toRSS ) ),
				array_keys( self::$_toRSS ) );

		foreach ( $xml->channel->children() as $node )
		{
			$tag = strtolower( $node->getName() );

			if ( $tag === 'item' )
			{
				$this->_entry[] = new FeedEntry( $node );
			}
			else if ( isset( $fromRSS[$tag] ) )
			{
				$this->_property( '_'.$fromRSS[$tag], (string)$node );
			}
		}
	}
}

/**
	Class: FeedEntry

	An Atom/RSS feed entry.
*/
class FeedEntry
{
	// Required
	protected $_id;
	protected $_title; // Required in RSS
	protected $_updated;

	// Recommended
	protected $_link; // Required in RSS

	// Optional
	protected $_author;
	protected $_category;
	protected $_content;
	protected $_contributor;
	protected $_published;
	protected $_rights;
	protected $_source;
	protected $_summary; // Required in RSS (as description)

	// Atom-RSS converter
	static protected $_toRSS = array(
		'id'        => 'guid',
		'title'     => 'title',
		'link'      => 'link',
		'author'    => 'author',
		'category'  => 'category',
		'published' => 'pubDate',
		'rights'    => 'copyright',
		'summary'   => 'description'
	);

	/**
		Constructor: __construct

		Parameters:
			$xml - (SimpleXMLElement) A XML entry.
	*/
	public function __construct( $xml = null )
	{
		if ( $xml instanceof SimpleXMLElement )
		{
			$tag = strtolower( $xml->getName() );

			switch ( $tag )
			{
				case 'item': // RSS
					$this->_parseRSS( $xml );
					break;

				case 'entry': // Atom
				default:
					$this->_parseAtom( $xml );
					break;
			}
		}
	}

	/**
		Method: id

		Get or set the ID of the entry.

		Parameters:
			$id - (string) The ID.
	*/
	public function id( $id = null )
	{
		return $this->_property( '_id', $id );
	}

	/**
		Method: title

		Get or set the title of the entry.

		Parameters:
			$title - (string) The title.
	*/
	public function title( $title = null )
	{
		return $this->_property( '_title', $title );
	}

	/**
		Method: updated

		Get or set the update date of the entry.

		Parameters:
			$updated - (string) The date.
	*/
	public function updated( $updated = null )
	{
		return $this->_property( '_updated', $updated );
	}

	/**
		Method: link

		Get or set the link of the entry.

		Parameters:
			$link - (string) The link.
	*/
	public function link( $link = null )
	{
		return $this->_property( '_link', $link );
	}

	/**
		Method: author

		Get or set the author of the entry.

		Parameters:
			$author - (string) The author.
	*/
	public function author( $author = null )
	{
		return $this->_property( '_author', $author );
	}

	/**
		Method: category

		Get or set the category of the entry.

		Parameters:
			$category - (string) The category.
	*/
	public function category( $category = null )
	{
		return $this->_property( '_category', $category );
	}

	/**
		Method: content

		Get or set the content of the entry.

		Parameters:
			$content - (string) The content.
	*/
	public function content( $content = null )
	{
		return $this->_property( '_content', $content );
	}

	/**
		Method: contributor

		Get or set the contributor of the feed.

		Parameters:
			$contributor - (string) The contributor.
	*/
	public function contributor( $contributor = null )
	{
		return $this->_property( '_contributor', $contributor );
	}

	/**
		Method: published

		Get or set the publication date of the feed.

		Parameters:
			$published - (string) The date.
	*/
	public function published( $published = null )
	{
		return $this->_property( '_published', $published );
	}

	/**
		Method: rights

		Get or set the rights of the entry.

		Parameters:
			$rights - (string) The rights.
	*/
	public function rights( $rights = null )
	{
		return $this->_property( '_rights', $rights );
	}

	/**
		Method: source

		Get or set the source of the entry.

		Parameters:
			$source - (string) The source.
	*/
	public function source( $source = null )
	{
		return $this->_property( '_source', $source );
	}

	/**
		Method: summary

		Get or set the summary of the entry.

		Parameters:
			$summary - (string) The summary.
	*/
	public function summary( $summary = null )
	{
		return $this->_property( '_summary', $summary );
	}

	// Set a property.
	protected function _property( $name, $value, $default = null )
	{
		if ( isset( $value ) )
		{
			$this->{$name} = $value;
		}
		else
		{
			return isset( $this->{$name} ) ? $this->{$name} : $default;
		}
		return $this;
	}

	// Parse an Atom entry
	protected function _parseAtom( $xml )
	{
		foreach ( $xml->children() as $node )
		{
			$tag = strtolower( $node->getName() );

			if ( property_exists( $this, '_'.$tag ) )
			{
				$this->_property( '_'.$tag, (string)$node );
			}
		}
	}

	// Parse a RSS entry
	protected function _parseRSS( $xml )
	{
		$fromRSS = array_combine(
				array_map( 'strtolower', array_values( self::$_toRSS ) ),
				array_keys( self::$_toRSS ) );

		foreach ( $xml->children() as $node )
		{
			$tag = strtolower( $node->getName() );

			if ( isset( $fromRSS[$tag] ) )
			{
				$this->_property( '_'.$fromRSS[$tag], (string)$node );
			}
		}
	}

	// Append an Atom XML entry
	public function _appendAtom( &$xml )
	{
		$xml->id      = $this->id();
		$xml->title   = $this->title();
		$xml->updated = $this->updated();

		$optional = array(
			'link',
			'author',
			'category',
			'content',
			'contributor',
			'published',
			'rights',
			'source',
			'summary' );

		foreach ( $optional as $tag )
		{
			$value = $this->{$tag}();

			if ( isset( $value ) )
			{
				$xml->{$tag} = $value;
			}
		}
	}

	// Append a RSS XML entry
	public function _appendRSS( &$xml )
	{
		$xml->title       = $this->title();
		$xml->description = $this->summary();
		$xml->link        = $this->link();

		$optional = array(
			'id',
			'author',
			'category',
			'published',
			'rights' );

		foreach ( $optional as $tag )
		{
			$value = $this->{$tag}();

			if ( isset( $value ) )
			{
				$xml->{self::$_toRSS[$tag]} = $value;
			}
		}
	}
}

