<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: Feed

	A class to manage Atom/RSS feeds.
	Basic support of Atom 1.0 and RSS 2.0 formats.

	Author:
		Charly LERSTEAU

	Date:
		2013-10-13
*/

//	Conversion Atom-RSS
//	http://www.intertwingly.net/wiki/pie/Rss20AndAtom10Compared#head-018c297098e131956bf394c0f7c8b6dd60f5cf78
//	
//	RSS 2.0                    | Atom 1.0               | Comments
//	---------------------------+------------------------+---------------------------------------------------------
//	rss                        | -                      | Vestigial in RSS
//	channel                    | feed                   | 
//	title                      | title                  | 
//	link                       | link                   | Atom defines an extensible family of rel values
//	description                | subtitle               | 
//	language                   | -                      | Atom uses standard xml:lang attribute
//	copyright                  | rights                 | 
//	webMaster                  | -                      | 
//	managingEditor             | author or contributor  |
//	pubDate                    | published (in entry)   | Atom has no feed-level equivalent
//	lastBuildDate (in channel) | updated                | RSS has no item-level equivalent
//	category                   | category               | 
//	generator                  | generator              | 
//	docs                       | -                      | 
//	cloud                      | -                      | 
//	ttl                        | -                      | <ttl> is problematic, prefer HTTP 1.1 cache control
//	image                      | logo                   | Atom recommends 2:1 aspect ratio
//	-                          | icon                   | As in favicon.ico
//	rating                     | -                      | 
//	textInput                  | -                      | 
//	skipHours                  | -                      | 
//	skipDays                   | -                      | 
//	item                       | entry                  | 
//	author                     | author                 | 
//	-                          | contributor            | 
//	description                | summary and/or content | depending on whether full content is provided
//	comments                   | -                      | 
//	enclosure                  | -                      | rel="enclosure" on <link> in Atom
//	guid                       | id                     | 
//	source                     | -                      | rel="via" on <link> in Atom
//	-                          | source                 | Container for feed-level metadata to support aggregation

/**
	Class: _FeedBase

	Base class of Feed elements.
*/
abstract class _FeedBase
{
	// Array of object types.
	static protected $_object = array(
		'author'      => '_FeedPerson',
		'contributor' => '_FeedPerson',
		'entry'       => '_FeedEntry',
		'link'        => '_FeedLink'
	);

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

	// Get a value of a property collection.
	protected function _propertyGet( $name, $index )
	{
		return isset( $index ) ? $this->{$name}[$index] : $this->{$name};
	}

	// Add a value to a property collection.
	protected function _propertyAdd( $name, $value )
	{
		$this->{$name}[] = $value;
		return end( $this->{$name} );
	}

	// Remove a value of a property collection.
	protected function _propertyRemove( $name, $index )
	{
		unset( $this->{$name}[$index] );
		return $this;
	}

	// Export as Atom/RSS.
	abstract protected function _write( &$xml, $type = null, $converter = null );
}

/**
	Class: _FeedEntryBase

	Base class of entry or feed.
*/
class _FeedEntryBase extends _FeedBase
{
	// Required
	protected $_id;
	protected $_title; // Required in RSS
	protected $_updated;

	// Optional
	protected $_author = array();
	protected $_category = array();
	protected $_contributor = array();
	protected $_link = array(); // Required in RSS (link[@rel="alternate"])

	/**
		Constructor: __construct
	*/
	public function __construct( $id = null, $title = null, $updated = null )
	{
		$this->id( $id );
		$this->title( $title );
		$this->updated( $updated );
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
		Method: author

		Get an author or the array of authors.

		Parameters:
			$index - (int) Author index.
	*/
	public function author( $index = null )
	{
		return $this->_propertyGet( '_author', $index );
	}

	/**
		Method: addAuthor

		Append an author.

		Returns:
			(object) The added author.
	*/
	public function addAuthor( $name = null, $uri = null, $email = null )
	{
		( $name instanceof _FeedPerson ) or $name = new _FeedPerson( $name, $uri, $email );
		return $this->_propertyAdd( '_author', $name );
	}

	/**
		Method: removeAuthor

		Delete an author.

		Parameters:
			$index - (int) Author index.
	*/
	public function removeAuthor( $index )
	{
		return $this->_propertyRemove( '_author', $index );
	}

	/**
		Method: category

		Get a category or the array of categories.

		Parameters:
			$index - (int) Category index.
	*/
	public function category( $index = null )
	{
		return $this->_propertyGet( '_category', $index );
	}

	/**
		Method: addCategory

		Append a category.
	*/
	public function addCategory( $category )
	{
		return $this->_propertyAdd( '_category', $category );
	}

	/**
		Method: removeCategory

		Delete a category.

		Parameters:
			$index - (int) Category index.
	*/
	public function removeCategory( $index )
	{
		return $this->_propertyRemove( '_category', $index );
	}

	/**
		Method: contributor

		Get a contributor or the array of contributors.

		Parameters:
			$index - (int) Contributor index.
	*/
	public function contributor( $index = null )
	{
		return $this->_propertyGet( '_contributor', $index );
	}

	/**
		Method: addContributor

		Append a contributor.

		Returns:
			(object) The added contributor.
	*/
	public function addContributor( $name = null, $uri = null, $email = null )
	{
		( $name instanceof _FeedPerson ) or $name = new _FeedPerson( $name, $uri, $email );
		return $this->_propertyAdd( '_contributor', $name );
	}

	/**
		Method: removeContributor

		Delete a contributor.

		Parameters:
			$index - (int) Contributor index.
	*/
	public function removeContributor( $index )
	{
		return $this->_propertyRemove( '_contributor', $index );
	}

	/**
		Method: link

		Get a link or the array of links.

		Parameters:
			$index - (int|string) Link index or rel attribute.
	*/
	public function link( $index = null )
	{
		if ( is_numeric( $index ) or !isset( $index ) )
		{
			return $this->_propertyGet( '_link', $index );
		}
		else foreach ( $this->_link as $link )
		{
			if ( $link->rel() == $index )
			{
				return $link;
			}
		}
		return null;
	}

	/**
		Method: addLink

		Append a link.

		Returns:
			(object) The added link.
	*/
	public function addLink( $href = null, $rel = null )
	{
		( $href instanceof _FeedLink ) or $href = new _FeedLink( $href, $rel );
		return $this->_propertyAdd( '_link', $href );
	}

	/**
		Method: removeLink

		Delete a link.

		Parameters:
			$index - (int) Link index.
	*/
	public function removeLink( $index )
	{
		return $this->_propertyRemove( '_link', $index );
	}

	// Parse a feed or entry.
	protected function _read( &$xml, $converter = null )
	{
		$self = get_class( $this );

		foreach ( $xml->children() as $node )
		{
			$tag = strtolower( $node->getName() );

			if ( isset( $converter ) )
			{
				if ( isset( $converter[$tag] ) )
				{
					$tag = strtolower( $converter[$tag] );
				}
				else
				{
					continue;
				}
			}

			// Unknown elements are ignored
			if ( in_array( $tag, $self::$_children ) )
			{
				// Is the element a simple string or a complex object ?
				if ( in_array( $tag, array_keys( self::$_object ) ) )
				{
					$class = self::$_object[$tag];
					$value = new $class( $node );
				}
				else
				{
					$value = (string)$node;
				}

				// Is the element a single value or a part of a collection ?
				if ( is_array( $this->{$tag}() ) )
				{
					$this->{'add'.$tag}( $value );
				}
				else
				{
					$this->{$tag}( $value );
				}
			}
		}
	}

	// Export as Atom/RSS.
	protected function _write( &$xml, $type = null, $converter = null )
	{
		$self = get_class( $this );
		foreach ( $self::$_children as $tag )
		{
			if ( isset( $converter ) and !isset( $converter[$tag] ) )
			{
				continue;
			}

			$value = $this->{$tag}();
			is_array( $value ) or $value = array( $value );
			isset( $converter ) and $tag = $converter[$tag];

			foreach ( $value as $v )
			{
				// Exception: single link for RSS (rel="alternate")
				if ( $type === 'rss' and ( $v instanceof _FeedLink ) and $v->rel() !== 'alternate' )
				{
					continue;
				}

				if ( $v instanceof _FeedBase )
				{
					$element = $xml->addChild( $tag );
					$v->_write( $element, $type, $converter );
				}
				else
				{
					$element = $xml->addChild( $tag, $v );
				}
			}
		}
	}
}

/**
	Class: _FeedEntry

	An Atom/RSS feed entry.
*/
class _FeedEntry extends _FeedEntryBase
{
	//	The "atom:entry" Element
	//	   atomEntry =
	//	      element atom:entry {
	//	         atomCommonAttributes,
	//	         (atomAuthor*
	//	          & atomCategory*
	//	          & atomContent?
	//	          & atomContributor*
	//	          & atomId
	//	          & atomLink*
	//	          & atomPublished?
	//	          & atomRights?
	//	          & atomSource?
	//	          & atomSummary?
	//	          & atomTitle
	//	          & atomUpdated
	//	          & extensionElement*)
	//	      }
	//	
	// 	Attributes inherited from FeedEntryBase:
	//		$_id
	//		$_title
	//		$_updated
	//		$_author
	//		$_category
	//		$_contributor
	//		$_link

	protected $_content;
	protected $_published;
	protected $_rights;
	protected $_source;
	protected $_summary; // Required in RSS (as description)

	// Children tags
	static protected $_children = array(
		'author',
		'category',
		'content',
		'contributor',
		'id',
		'link',
		'published',
		'rights',
		'source',
		'summary',
		'title',
		'updated'
	);

	static protected $_fromRSS = array(
		'author'        => 'author',
		'category'      => 'category',
		'copyright'     => 'rights',
		'description'   => 'content',
		'guid'          => 'id',
		'link'          => 'link',
		'pubdate'       => 'published',
		'title'         => 'title'
	);

	static protected $_toRSS = array(
		'author'      => 'author',
		'category'    => 'category',
		'content'     => 'description',
		'contributor' => 'author',
		'id'          => 'guid',
		'link'        => 'link',
		'published'   => 'pubDate',
		'rights'      => 'copyright',
		'summary'     => 'description',
		'title'       => 'title'
	);

	/**
		Constructor: __construct
	*/
	public function __construct( $id = null, $title = null, $updated = null )
	{
		if ( $id instanceof SimpleXMLElement )
		{
			$tag = strtolower( $id->getName() );

			switch ( $tag )
			{
				case 'item': // RSS
					$this->_read( $id, self::$_fromRSS );
					break;

				case 'entry': // Atom
				default:
					$this->_read( $id );
					break;
			}
		}
		else
		{
			parent::__construct( $id, $title, $updated );
		}
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
}

/**
	Class: _FeedPerson

	An Atom/RSS feed person (author or contributor).
*/
class _FeedPerson extends _FeedBase
{
	//	Person Construct:
	//	   atomPersonConstruct =
	//	      atomCommonAttributes,
	//	      (element atom:name { text }
	//	       & element atom:uri { atomUri }?
	//	       & element atom:email { atomEmailAddress }?
	//	       & extensionElement*)

	protected $_name;
	protected $_uri;
	protected $_email;

	// Children tags
	static protected $_children = array(
		'name',
		'uri',
		'email'
	);

	/**
		Constructor: __construct
	*/
	public function __construct( $name = null, $uri = null, $email = null )
	{
		if ( $name instanceof SimpleXMLElement )
		{
			if ( $name->count() > 0 ) // Atom
			{
				$this->name ( (string)$name->name );
				$this->uri  ( (string)$name->uri );
				$this->email( (string)$name->email );
			}
			else // RSS
			{
				$this->name( (string)$name );
			}
		}
		else
		{
			$this->name ( $name );
			$this->uri  ( $uri );
			$this->email( $email );
		}
	}

	/**
		Method: name

		Get or set the name of the person.

		Parameters:
			$name - (string) The name.
	*/
	public function name( $name = null )
	{
		return $this->_property( '_name', $name );
	}

	/**
		Method: uri

		Get or set the URI of the person.

		Parameters:
			$uri - (string) The source.
	*/
	public function uri( $uri = null )
	{
		return $this->_property( '_uri', $uri );
	}

	/**
		Method: email

		Get or set the email of the person.

		Parameters:
			$email - (string) The email.
	*/
	public function email( $email = null )
	{
		return $this->_property( '_email', $email );
	}

	// Export as Atom/RSS.
	public function _write( &$xml, $type = null, $converter = null )
	{
		if ( $type === 'rss' )
		{
			$dom = dom_import_simplexml( $xml );
			$dom->nodeValue = $this->name();
		}
		else foreach ( self::$_children as $name )
		{
			$value = $this->{$name}();

			if ( isset( $value ) )
			{
				$xml->addChild( $name, $value );
			}
		}
	}
}

/**
	Class: _FeedLink

	An Atom/RSS link.
*/
class _FeedLink extends _FeedBase
{
	//	The "atom:link" Element
	//	   atomLink =
	//	      element atom:link {
	//	         atomCommonAttributes,
	//	         attribute href { atomUri },
	//	         attribute rel { atomNCName | atomUri }?,
	//	         attribute type { atomMediaType }?,
	//	         attribute hreflang { atomLanguageTag }?,
	//	         attribute title { text }?,
	//	         attribute length { text }?,
	//	         undefinedContent
	//	      }

	protected $_href;
	protected $_rel;
	protected $_type;
	protected $_hreflang;
	protected $_title;
	protected $_length;

	// Attribute names
	static protected $_attributes = array(
		'href',
		'rel',
		'type',
		'hreflang',
		'title',
		'length'
	);

	/**
		Constructor: __construct
	*/
	public function __construct( $href = null, $rel = null )
	{
		if ( $href instanceof SimpleXMLElement )
		{
			// Check if Atom or RSS
			$dom = dom_import_simplexml( $href );

			// RSS
			if ( $dom->ownerDocument->documentElement->nodeName === 'rss' )
			{
				$this->href( (string)$href );
				$this->rel( 'alternate' );
			}
			else // Atom
			{
				$this->href    ( (string)$href->attributes()->href );
				$this->rel     ( (string)$href->attributes()->rel );
				$this->type    ( (string)$href->attributes()->type );
				$this->hreflang( (string)$href->attributes()->hreflang );
				$this->title   ( (string)$href->attributes()->title );
				$this->length  ( (string)$href->attributes()->length );
			}
		}
		else
		{
			$this->href( (string)$href );
			$this->rel ( (string)$rel );
		}
	}

	/**
		Method: href

		Get or set the URL of the link.

		Parameters:
			$href - (string) The URL.
	*/
	public function href( $href = null )
	{
		return $this->_property( '_href', $href );
	}

	/**
		Method: rel

		Get or set the rel attribute of the link.

		Parameters:
			$rel - (string) The rel attribute.
	*/
	public function rel( $rel = null )
	{
		return $this->_property( '_rel', $rel );
	}

	/**
		Method: type

		Get or set the type of the link.

		Parameters:
			$type - (string) The type.
	*/
	public function type( $type = null )
	{
		return $this->_property( '_type', $type );
	}

	/**
		Method: hreflang

		Get or set the hreflang attribute of the link.

		Parameters:
			$hreflang - (string) The hreflang attribute.
	*/
	public function hreflang( $hreflang = null )
	{
		return $this->_property( '_hreflang', $hreflang );
	}

	/**
		Method: title

		Get or set the title of the link.

		Parameters:
			$title - (string) The title.
	*/
	public function title( $title = null )
	{
		return $this->_property( '_title', $title );
	}

	/**
		Method: length

		Get or set the length of the link.

		Parameters:
			$length - (string) The length.
	*/
	public function length( $length = null )
	{
		return $this->_property( '_length', $length );
	}

	// Export as Atom/RSS.
	public function _write( &$xml, $type = null, $converter = null )
	{
		if ( $type === 'rss' )
		{
			$dom = dom_import_simplexml( $xml );
			$dom->nodeValue = $this->href();
		}
		else foreach ( self::$_attributes as $name )
		{
			$value = $this->{$name}();

			if ( isset( $value ) )
			{
				$xml->addAttribute( $name, $value );
			}
		}
	}
}

/**
	Class: Feed

	A class to manage Atom/RSS feeds.
	Basic support of Atom 1.0 and RSS 2.0 formats.
*/
class Feed extends _FeedEntryBase
{
	//	The "atom:feed" Element
	//	    atomFeed =
	//	      element atom:feed {
	//	         atomCommonAttributes,
	//	         (atomAuthor*
	//	          & atomCategory*
	//	          & atomContributor*
	//	          & atomGenerator?
	//	          & atomIcon?
	//	          & atomId
	//	          & atomLink*
	//	          & atomLogo?
	//	          & atomRights?
	//	          & atomSubtitle?
	//	          & atomTitle
	//	          & atomUpdated
	//	          & extensionElement*),
	//	         atomEntry*
	//	      }
	//	
	// 	Attributes inherited from FeedEntryBase:
	//		$_id
	//		$_title
	//		$_updated
	//		$_author
	//		$_category
	//		$_contributor
	//		$_link

	protected $_generator;
	protected $_icon;
	protected $_logo;
	protected $_rights;
	protected $_subtitle; // Required in RSS (as description)
	protected $_entry = array(); // item in RSS

	// Children tags
	static protected $_children = array(
		'author',
		'category',
		'contributor',
		'generator',
		'icon',
		'id',
		'link',
		'logo',
		'rights',
		'subtitle',
		'title',
		'updated',
		'entry'
	);

	static protected $_fromRSS = array(
		'author'        => 'author',
		'category'      => 'category',
		'copyright'     => 'rights',
		'description'   => 'subtitle',
		'generator'     => 'generator',
		'guid'          => 'id',
		'link'          => 'link',
		'image'         => 'logo',
		'title'         => 'title',
		'lastbuilddate' => 'updated',
		'item'          => 'entry'
	);

	static protected $_toRSS = array(
		'author'      => 'author',
		'category'    => 'category',
		'contributor' => 'author',
		'generator'   => 'generator',
		'id'          => 'guid',
		'link'        => 'link',
		'logo'        => 'image',
		'rights'      => 'copyright',
		'subtitle'    => 'description',
		'title'       => 'title',
		'updated'     => 'lastBuildDate',
		'entry'       => 'item'
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
				$this->_read( $content->channel, self::$_fromRSS );
				break;

			case 'feed': // Atom
			default:
				$this->_read( $content );
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
		return count( $this->_entry );
	}

	/**
		Method: entry

		Get an entry.

		Parameters:
			$index - (int) The entry index.
	*/
	public function entry( $index = null )
	{
		return $this->_propertyGet( '_entry', $index );
	}

	/**
		Method: addEntry

		Append an entry.
	*/
	public function addEntry( $id = null, $title = null, $updated = null )
	{
		( $id instanceof _FeedEntry ) or $id = new _FeedEntry( $id, $title, $updated );
		return $this->_propertyAdd( '_entry', $id );
	}

	/**
		Method: removeEntry

		Delete an entry.

		Parameters:
			$index - (int) The entry index.
	*/
	public function removeEntry( $index )
	{
		return $this->_propertyRemove( '_entry', $index );
	}

	/**
		Method: asAtom

		Export as Atom.
	*/
	public function asAtom()
	{
		$xml = new SimpleXMLElement( '<feed xmlns="http://www.w3.org/2005/Atom"/>' );
		$this->_write( $xml );
		return $xml->asXML();
	}

	/**
		Method: asRSS

		Export as RSS.
	*/
	public function asRSS()
	{
		$xml = new SimpleXMLElement( '<rss version="2.0"><channel/></rss>' );
		$this->_write( $xml->channel, 'rss', self::$_toRSS );
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
}

