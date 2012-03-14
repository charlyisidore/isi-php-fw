<?php if ( !defined( 'SECURITY_CONST' ) ) die( 'Access Denied' );

/**
	Class: Mail

	An easy-to-use mail sender.

	Author:
		Charly Lersteau

	Date:
		2012-03-14

	Extends:
		<MailPart>

	Example:
		>	$mail = new Mail();
		>	$mail->to( 'test@mail.com' )
		>		->subject( 'This is a test' )
		>		->from( 'admin@mail.com' )
		>		->attachment( 'file.tar.gz' )
		>		->image( 'image.jpg' );
		>
		>	// Retrieve Content-ID of the image and put it in the HTML.
		>	$cid  = $mail->image( 0 )->contentID();
		>	$html = "<h1>Hello !</h1><img src=\"cid:{$cid}\" />";
		>
		>	$mail->html( $html );
		>	if ( $mail->send() )
		>	{
		>		echo 'Success!';
		>	}
		>	else
		>	{
		>		echo 'Failure!';
		>	}
*/
class Mail extends MailPart
{
	const CRLF     = "\r\n";
	const WORDWRAP = 80;

	protected $_to              = '';
	protected $_subject         = '';
	protected $_text            = null;
	protected $_html            = null;
	protected $_messageCharset  = 'utf-8';
	protected $_messageEncoding = 'quoted-printable';
	protected $_attachment      = array();
	protected $_image           = array();
	protected $_mail;

	/**
		Constructor: __construct
	*/
	public function __construct(
		$to      = array(),
		$subject = '',
		$message = null,
		$header  = array() )
	{
		$this->to( $to );
		$this->subject( $subject );
		$this->text( $message );
		$this->header( 'MIME-Version', '1.0' );
		$this->header( $header );
	}

	/**
		Method: factory

		Create a new Mail instance.

		Returns:
			(self) A new Mail instance.
	*/
	public static function factory(
		$to      = array(),
		$subject = '',
		$message = null,
		$header  = array() )
	{
		return new self( $to, $subject, $message, $header );
	}

	/**
		Method: text

		Set the text message of the email.

		Parameters:
			$message - (string) The text message.
	*/
	public function text( $message = null )
	{
		return $this->_property( '_text', $message );
	}

	/**
		Method: html

		Set the html message of the email.

		Parameters:
			$message - (string) The html message.
	*/
	public function html( $message = null )
	{
		return $this->_property( '_html', $message );
	}

	/**
		Method: to

		Set receivers for the mail.

		Syntax:
			>	$mail->to( a [, ... ] );
			or
			>	$mail->to( array( a, ... ) );
	*/
	public function to()
	{
		$arguments = func_get_args();
		return $this->_propertyHeader( 'to', $arguments );
	}

	/**
		Method: subject

		Set the subject of the email.

		Parameters:
			$subject - (string) The subject.
	*/
	public function subject( $subject = null )
	{
		return $this->_propertyHeader( 'subject', $subject );
	}

	/**
		Method: from

		Set the "From" header.

		Syntax:
			>	$mail->from( a [, ... ] );
			or
			>	$mail->from( array( a, ... ) );
	*/
	public function from()
	{
		$arguments = func_get_args();
		return $this->_propertyHeader( 'from', $arguments );
	}

	/**
		Method: sender

		Set the "Sender" header.

		Parameters:
			$sender - (string) The sender.
	*/
	public function sender( $sender = null )
	{
		return $this->_propertyHeader( 'sender', $sender );
	}

	/**
		Method: replyTo

		Set the "Reply-To" header.

		Syntax:
			>	$mail->replyTo( a [, ... ] );
			or
			>	$mail->replyTo( array( a, ... ) );
	*/
	public function replyTo()
	{
		$arguments = func_get_args();
		return $this->_propertyHeader( 'reply-to', $arguments );
	}

	/**
		Method: cc

		Set the "Cc" header.

		Syntax:
			>	$mail->cc( a [, ... ] );
			or
			>	$mail->cc( array( a, ... ) );
	*/
	public function cc()
	{
		$arguments = func_get_args();
		return $this->_propertyHeader( 'cc', $arguments );
	}

	/**
		Method: bcc

		Set the "Bcc" header.

		Syntax:
			>	$mail->bcc( a [, ... ] );
			or
			>	$mail->bcc( array( a, ... ) );
	*/
	public function bcc()
	{
		$arguments = func_get_args();
		return $this->_propertyHeader( 'bcc', $arguments );
	}

	/**
		Method: messageCharset

		Set the charset of the message.

		Parameters:
			$charset - (string) The charset ('UTF-8', 'ISO-8859-1'...).
	*/
	public function messageCharset( $charset = null )
	{
		return $this->_property( '_messageCharset', $charset );
	}

	/**
		Method: messageEncoding

		Set the encoding of the message.

		Parameters:
			$encoding - (string) 7bit, 8bit, quoted-printable or base64.
	*/
	public function messageEncoding( $encoding = null )
	{
		return $this->_property( '_messageEncoding', $encoding );
	}

	/**
		Method: attachment

		Attach a file.

		Parameters:
			$file - (string) The file path.
			$name - (optional) (string) Alternative file name.
	*/
	public function attachment( $file, $name = null )
	{
		if ( is_int( $file ) )
		{
			return isset( $this->_attachment[ $file ] )
				? $this->_attachment[ $file ]
				: null;
		}
		else
		{
			$attachment = new MailAttachment( $file, 'attachment' );
			$attachment->filename( $name );
			$this->_attachment[] = $attachment;
		}
		return $this;
	}

	/**
		Method: image

		Attach an image with Content-ID.

		Parameters:
			$file - (mixed) The file path or index.
			$cid - (optional) (string) Content-ID. Generated if not specified.
	*/
	public function image( $file, $cid = null )
	{
		if ( is_int( $file ) )
		{
			return isset( $this->_image[ $file ] )
				? $this->_image[ $file ]
				: null;
		}
		else
		{
			// Generate Content-ID
			if ( !isset( $cid ) )
			{
				$unique = array(
					uniqid( '', true ),
					(string)microtime( true )
				);
				$cid = implode( '.', $unique ).'@'.$_SERVER[ 'SERVER_NAME' ];
			}
			$image = new MailAttachment( $file, 'inline' );
			$image->contentID( $cid );
			$this->_image[] = $image;
		}
		return $this;
	}

	/**
		Method: send

		Returns TRUE if the mail was successfully accepted for delivery,
		FALSE otherwise.

		Returns:
			(bool)
	*/
	public function send()
	{
		$this->build();

		$to      = $this->to();
		$subject = $this->subject();
		$content = $this->_mail->content();
		$header  = $this->_mail->header();
		return mail( $to, $subject, $content, $header );
	}

	// Stores a new MailPart instance in $this->_mail.
	public function build()
	{
		$text     = $this->text();
		$html     = $this->html();
		$charset  = $this->messageCharset();
		$encoding = $this->messageEncoding();

		// text/plain part is needed for compatibility with old clients.
		$textPart = new MailPart;
		$textPart->contentType( 'text/plain', array( 'charset' => $charset ) );
		$textPart->contentTransferEncoding( $encoding );
		$textPart->content( $text );

		if ( isset( $html ) )
		{
			$htmlPart = new MailPart;
			$htmlPart->contentType( 'text/html', array( 'charset' => $charset ) );
			$htmlPart->contentTransferEncoding( $encoding );
			$htmlPart->content( $html );

			if ( !isset( $text ) )
			{
				$textPart->content( trim( strip_tags( $html ) ) );
			}

			// multipart/alternative
			//     text/plain
			$message = new MailMultipart( 'alternative' );
			$message->attach( $textPart );

			if ( count( $this->_image ) > 0 )
			{
				// multipart/related
				//     text/html
				//     (images)
				$relatedPart = new MailMultipart( 'related' );
				$relatedPart->attach( $htmlPart );

				foreach ( $this->_image as $image )
				{
					$relatedPart->attach( $image );
				}

				$message->attach( $relatedPart );
			}
			else
			{
				// text/html
				$message->attach( $htmlPart );
			}
		}
		else
		{
			// text/plain
			$message = $textPart;
		}

		if ( count( $this->_attachment ) > 0 )
		{
			// multipart/mixed
			$mail = new MailMultipart( 'mixed' );
			$mail->attach( $message );

			foreach ( $this->_attachment as $attachment )
			{
				$mail->attach( $attachment );
			}
		}
		else
		{
			$mail = $message;
		}

		foreach ( $this->_header as $name => $value )
		{
			if ( !in_array( $name, array( 'to', 'subject' ) ) )
			{
				$mail->header( $name, $value );
			}
		}

		$this->_mail = $mail->build();
	}

	// Return mail without 'To' and 'Subject' headers.
	public function __toString()
	{
		$this->build();
		// PHP < 5.2.0 __toString() compatibility.
		// See http://www.php.net/manual/en/language.oop5.magic.php#object.tostring
		return $this->_mail->__toString();
	}

	// Set a header property (implode if array)
	protected function _propertyHeader( $name, $value )
	{
		if ( is_array( $value ) )
		{
			$value = MailEncode::join( $value );
		}

		if ( isset( $value ) )
		{
			$this->header( $name, $value );
		}
		else
		{
			return $this->header( $name );
		}
		return $this;
	}
}

/**
	Class: MailAttachment

	An file attachment MIME part.

	Extends:
		<MailPart>
*/
class MailAttachment extends MailPart
{
	protected $_localfile;
	protected $_filename;
	protected $_disposition;

	public function __construct( $localfile, $disposition )
	{
		$this->_localfile = $localfile;
		$this->filename( $localfile );
		$this->disposition( $disposition );
		$this->contentTransferEncoding( 'base64' );
	}

	/**
		Method: filename

		Get the alternative file name.

		Returns:
			(string)
	*/
	public function filename( $filename = null )
	{
		if ( isset( $filename ) )
		{
			$filename = basename( $filename );
		}
		return $this->_property( '_filename', $filename );
	}

	/**
		Method: disposition

		Get the Content-Disposition header.

		Returns:
			(string)
	*/
	public function disposition( $disposition = null )
	{
		return $this->_property( '_disposition', $disposition );
	}

	/**
		Method: contentID

		Get the Content-ID to display inline images.

		Returns:
			(string)
	*/
	public function contentID( $cid = null )
	{
		if ( isset( $cid ) )
		{
			$cid = trim( $cid, '<>' );
			$cid = "<{$cid}>";
			$this->header( 'Content-ID', $cid );
		}
		else
		{
			return trim( $this->header( 'Content-ID' ), '<>' );
		}
		return $this;
	}

	// Retrieve MIME type if possible.
	public function mimeType()
	{
		if ( class_exists( 'finfo' ) )
		{
			$info = new finfo( FILEINFO_MIME );
			$type = $info->file( $this->_localfile );
		}
		else if ( function_exists( 'mime_content_type' ) )
		{
			$type = mime_content_type( $this->_localfile );
		}
		else
		{
			$type = 'application/octet-stream';
		}
		return $type;
	}

	public function build()
	{
		$content = file_get_contents( $this->_localfile );

		return $this
			->header(
				'Content-Disposition',
				$this->disposition(),
				array( 'filename' => $this->filename() )
			)
			->contentType( $this->mimeType() )
			->content( $content );
	}
}

/**
	Class: MailPart

	A simple MIME part with a header and a body.
*/
class MailPart
{
	protected $_header   = array();
	protected $_charset  = 'UTF-8';
	protected $_encoding = 'Q';
	protected $_content  = '';

	/**
		Method: header

		Get or set a header.

		Parameters:
			$name - (string) The header name.
			$value - (optional) (string) The header value.
			$attributes - (optional) (array) The header attributes.

		Returns:
			(mixed)
	*/
	public function header( $name = null, $value = null, $attributes = null )
	{
		// Return all
		if ( !isset( $name ) )
		{
			$header = array();
			$keys   = array_keys( $this->_header );
			foreach ( $keys as $key )
			{
				$field = MailEncode::titleCase( $key );
				$header[] = $field.': '.$this->header( $key );
			}
			return implode( Mail::CRLF, $header );
		}
		// Set key-value pairs
		else if ( is_array( $name ) )
		{
			foreach ( $name as $n => $v )
			{
				$this->header( $n, $v );
			}
		}
		else
		{
			// A field name MUST be composed of printable US-ASCII characters.
			$name = MailEncode::fieldName( $name );

			// Set value
			if ( isset( $value ) )
			{
				if ( is_array( $attributes ) )
				{
					foreach ( $attributes as $n => $v )
					{
						$v = addcslashes( $v, '"' );
						$value .= ";\r\n\t{$n}=\"{$v}\"";
					}
				}
				$this->_header[ $name ] = MailEncode::fieldValue( $value );
			}
			// Return encoded header
			else
			{
				return isset( $this->_header[ $name ] )
					? MailEncode::header(
						$this->_header[ $name ],
						$this->charset(),
						$this->encoding()
					)
					: null;
			}
		}
		return $this;
	}

	/**
		Method: charset

		Set header charset.

		Parameters:
			$charset - (string) The charset (example: 'UTF-8').
	*/
	public function charset( $charset = null )
	{
		return $this->_property( '_charset', $charset );
	}

	/**
		Method: encoding

		Set header encoding ('Q' or 'B').

		Parameters:
			$encoding - (string) The encoding ('Q' or 'B').
	*/
	public function encoding( $encoding = null )
	{
		return $this->_property( '_encoding', $encoding );
	}

	// Content-Type header.
	public function contentType( $type = null, $attributes = null )
	{
		return $this->header( 'Content-Type', $type, $attributes );
	}

	// Content-Transfer-Encoding header.
	public function contentTransferEncoding( $encoding = null )
	{
		return $this->header( 'Content-Transfer-Encoding', $encoding );
	}

	// Set the body.
	public function content( $content = null )
	{
		if ( isset( $content ) )
		{
			$this->_content = $content;
		}
		else
		{
			return isset( $this->_content )
				? MailEncode::content(
					$this->_content,
					$this->contentTransferEncoding()
				)
				: null;
		}
		return $this;
	}

	// Function to make a header and a body (children classes).
	public function build()
	{
		return $this;
	}

	// Return all the mail part.
	public function __toString()
	{
		$this->build();
		$header  = $this->header();
		$content = $this->content();
		return "{$header}\n\n{$content}";
	}

	// Set a property (implode if array).
	protected function _property( $name, $value, $default = null )
	{
		if ( is_array( $value ) )
		{
			$value = MailEncode::join( $value );
		}

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
}

// A MIME part with sub-parts. Does not need to be documented.
class MailMultipart extends MailPart
{
	const MESSAGE = "This is a message with multiple parts in MIME format.\n";

	protected $_type;
	protected $_part;

	// $type is 'mixed' or 'alternative'.
	public function __construct( $type )
	{
		$this->_type = $type;
	}

	public function attach( MailPart $part )
	{
		$this->_part[] = $part;
	}

	public function build()
	{
		$boundary = sha1( microtime() );
		$content  = self::MESSAGE;
		$type     = $this->_type;

		foreach ( $this->_part as $part )
		{
			// PHP < 5.2.0 __toString() compatibility.
			// See http://www.php.net/manual/en/language.oop5.magic.php#object.tostring
			$content .= "--{$boundary}\n{$part->__toString()}\n";
		}
		$content .= "--{$boundary}--";

		return $this
			->contentType( "multipart/{$type}", array( 'boundary' => $boundary ) )
			->content( $content );
	}
}

// Some useful encoding functions.
class MailEncode
{
	// Encode a header value.
	public static function header( $value, $charset, $encoding )
	{
		if ( extension_loaded( 'mbstring' ) )
		{
			// Check if it is necessary to encode (ascii or not).
			if ( !mb_check_encoding( $value, 'us-ascii' ) )
			{
				// mb_internal_encoding() should be set to same encoding as $charset.
				$internal = mb_internal_encoding();
				mb_internal_encoding( $charset );

				$value = mb_encode_mimeheader( $value, $charset, $encoding );

				// Restore previous internal encoding.
				mb_internal_encoding( $internal );
			}
		}
		else
		{
			// Check if it is necessary to encode (ascii or not).
			if ( !preg_match( '/^[\x20-\x7f]*$/D', $value ) )
			{
				// If mbstring is not activated,
				// We ignore $encoding parameter and use base64.
				$value = '=?'.$charset.'?B?'.base64_encode( $value ).'?=';
			}
		}
		return $value;
	}

	// Translate a field name (lowercase).
	public static function fieldName( $name )
	{
		// RFC 5322 2.2 Header Fields
		// A field name MUST be composed of printable US-ASCII characters
		// (i.e., characters that have values between 33 and 126, inclusive),
		// except colon.
		$name = preg_replace( '/[^[:graph:]]/', '?', $name );
		$name = strtr( $name, ':', '?' );
		return strtolower( $name );
	}

	// Prevents from mail header injection in (multiline) field values.
	// Adds a whitespace before each new line.
	public static function fieldValue( $value )
	{
		// RFC 5322 2.2.3 Long Header Fields
		// Each header field is logically a single line of characters comprising
		// the field name, the colon, and the field body.  For convenience
		// however, and to deal with the 998/78 character limitations per line,
		// the field body portion of a header field can be split into a
		// multiple-line representation; this is called "folding".  The general
		// rule is that wherever this specification allows for folding white
		// space (not simply WSP characters), a CRLF may be inserted before any
		// WSP.
		return preg_replace( '/([\r\n])(\S)/', "$1\t$2", $value );
	}

	// Title capitalization.
	public static function titleCase( $value )
	{
		if ( is_callable( 'mb_convert_case' ) )
		{
			return mb_convert_case( $value, MB_CASE_TITLE, 'us-ascii' );
		}
		else
		{
			// If mbstring is not activated, just use ucwords.
			return ucwords( $value );
		}
	}

	// Encode a body.
	public static function content( $value, $encoding )
	{
		switch ( strtolower( $encoding ) )
		{
			case 'quoted-printable':
				if ( function_exists( 'quoted_printable_encode' ) )
				{
					$value = quoted_printable_encode( $value );
				}
				else if ( function_exists( 'imap_8bit' ) )
				{
					$value = imap_8bit( $value );
				}
				else
				{
					$value = self::quotedPrintable( $value );
				}
				break;
			case 'base64':
				$value = base64_encode( $value );
				$value = chunk_split( $value );
				break;
			default:
				$value = wordwrap( $value, Mail::WORDWRAP );
				$value = str_replace( "\n.", "\n..", $value );
				break;
		}
		return $value;
	}

	// The same as quoted_printable_encode but for PHP < 5.3.
	// See http://www.php.net/manual/fr/function.imap-8bit.php#61216
	public static function quotedPrintable( $value )
	{
		// split text into lines
		$aLines = explode( chr( 13 ).chr( 10 ), $value );

		for ( $i = 0; $i < count( $aLines ); $i++ )
		{
			$sLine = &$aLines[ $i ];

			if ( strlen( $sLine ) === 0 )
			{
				continue; // do nothing, if empty
			}

			$sRegExp   = '/[^\x20\x21-\x3C\x3E-\x7E]/e';
			$sReplmt   = 'sprintf( "=%02X", ord ( "$0" ) ) ;';
			$sLine     = preg_replace( $sRegExp, $sReplmt, $sLine );
			$iLength   = strlen( $sLine );
			$iLastChar = ord( $sLine{$iLength-1} );

			if ( $i != count( $aLines ) - 1 )
			{
				if ( $iLastChar == 0x09 or $iLastChar == 0x20 )
				{
					$sLine{$iLength-1} = '=';
					$sLine .= ( $iLastChar == 0x09 ) ? '09' : '20';
				}
			}

			$sLine = str_replace( ' =0D', '=20=0D', $sLine );

			preg_match_all( '/.{1,73}([^=]{0,2})?/', $sLine, $aMatch );
			$sLine = implode( '='.chr( 13 ).chr( 10 ), $aMatch[ 0 ] );
		}
		// join lines into text
		return implode( chr( 13 ).chr( 10 ), $aLines );
	}

	// Join value list (return null if empty array).
	public static function join( $value )
	{
		$value = self::flatten( $value );
		$value = array_map( 'trim', $value );
		return !empty( $value ) ? implode( ',', $value ) : null;
	}

	// Flatten an array.
	public static function flatten( $array )
	{
		$result = array();
		$it = new RecursiveIteratorIterator( new RecursiveArrayIterator( $array ) );

		foreach ( $it as $value )
		{
			$result[] = $value;
		}
		return $result;
	}
}