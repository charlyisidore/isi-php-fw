<?php defined( 'SECURITY_CONST' ) or die( 'Access Denied' );

/**
	Class: Image

	Image handler with chaining methods.

	Author:
		Charly Lersteau

	Date:
		2013-03-24 (unstable)
*/
class Image
{
	protected $_image;
	protected $_fillColor;
	protected $_background;
	protected $_strokeColor = 0x000000;
	protected $_strokeWidth = 1;
	protected $_font;
	protected $_fontSize    = 16;
	protected $_quality     = 75; // IJG
	protected $_compression = 9;

	/**
		Constructor: __construct

		Parameters:
			... - (mixed) File name or width, height arguments.
	*/
	public function __construct( $x, $y = null )
	{
		if ( is_null( $y ) )
		{
			$this->_filename = $x;
			$this->_image = $this->_load( $this->_filename );
		}
		else
		{
			$this->_image = $this->_create( $x, $y );
		}
	}

	/**
		Destructor: __destruct

		Destroy the image.
	*/
	public function __destruct()
	{
		$this->_destroy();
	}

	/**
		Method: factory

		Create a new Image instance.

		Parameters:
			... - (mixed) File name or width, height arguments.

		Returns:
			(self) A new Image instance.
	*/
	public static function factory( $x, $y = null )
	{
		return new self( $x, $y );
	}

	/**
		Method: width

		Set the image width.

		Parameters:
			$width - (optional) (int) The image width in pixels.
	*/
	public function width( $width = null )
	{
		if ( is_null( $width ) )
		{
			return imagesx( $this->_image );
		}
		else
		{
			$this->scale( $width, $this->height() );
		}
		return $this;
	}

	/**
		Method: height

		Set the image height.

		Parameters:
			$height - (optional) (int) The image height in pixels.
	*/
	public function height( $height = null )
	{
		if ( is_null( $height ) )
		{
			return imagesy( $this->_image );
		}
		else
		{
			$this->scale( $this->width(), $height );
		}
		return $this;
	}

	/**
		Method: resize

		Set the dimensions of the image.

		Parameters:
			$width - (int|float) The image width in pixels.
			$height - (optional) (int) The image height in pixels.

		Returns:
			self
	*/
	public function resize( $width, $height = null )
	{
		$oldWidth  = $this->width();
		$oldHeight = $this->height();
		$ratio     = (float)$oldWidth / (float)$oldHeight;

		if ( !isset( $height ) ) // %
		{
			$height = round( $width * $oldHeight );
			$width  = round( $width * $oldWidth );
		}
		else if ( empty( $width ) ) // px
		{
			$width = round( $height * $ratio );
		}
		else if ( empty( $height ) ) // px
		{
			$height = round( $width / $ratio );
		}

		$image = $this->_create( $width, $height );
		imagecopyresampled(
			$image, $this->_image,
			0, 0, 0, 0,
			$width, $height, $oldWidth, $oldHeight
		);

		$this->_destroy();
		$this->_image = $image;
		return $this;
	}

	/**
		Method: scale

		Set the dimensions of the image without modifying ratio.

		Parameters:
			$width - (int|float) The image width in pixels.
			$height - (optional) (int) The image height in pixels.

		Returns:
			self
	*/
	public function scale( $width, $height = null )
	{
		$oldWidth  = $this->width();
		$oldHeight = $this->height();
		$ratio     = (float)$oldWidth / (float)$oldHeight;

		if ( !isset( $height ) ) // %
		{
			$height = round( $width * $oldHeight );
			$width  = round( $width * $oldWidth );
		}
		else if ( empty( $width ) ) // px
		{
			$width = round( $height * $ratio );
		}
		else if ( empty( $height ) ) // px
		{
			$height = round( $width / $ratio );
		}

		// Keep ratio
		if ( (float)$width / (float)$height > $ratio )
		{
			$w = $height * $ratio;
			$h = $height;
			$x = ( $width - $w ) / 2.;
			$y = 0;
		}
		else
		{
			$w = $width;
			$h = $width / $ratio;
			$x = 0;
			$y = ( $height - $h ) / 2.;
		}

		$image = $this->_create( $width, $height );
		imagecopyresampled(
			$image, $this->_image,
			$x, $y, 0, 0,
			$w, $h, $oldWidth, $oldHeight
		);

		$this->_destroy();
		$this->_image = $image;
		return $this;
	}

	/**
		Method: thumbnail

		Thumbnail the image.

		Parameters:
			$width - (int|float) The image width in pixels.
			$height - (optional) (int) The image height in pixels.

		Returns:
			self
	*/
	public function thumbnail( $width, $height = null )
	{
		$oldWidth  = $this->width();
		$oldHeight = $this->height();
		$ratio     = (float)$oldWidth / (float)$oldHeight;

		if ( !isset( $height ) ) // %
		{
			$height = round( $width * $oldHeight );
			$width  = round( $width * $oldWidth );
		}
		else if ( empty( $width ) ) // px
		{
			$width = round( $height * $ratio );
		}
		else if ( empty( $height ) ) // px
		{
			$height = round( $width / $ratio );
		}

		// Keep ratio
		if ( (float)$width / (float)$height > $ratio )
		{
			$w = $width;
			$h = $width / $ratio;
			$x = 0;
			$y = ( $height - $h ) / 2.;
		}
		else
		{
			$w = $height * $ratio;
			$h = $height;
			$x = ( $width - $w ) / 2.;
			$y = 0;
		}

		$image = $this->_create( $width, $height );
		imagecopyresampled(
			$image, $this->_image,
			$x, $y, 0, 0,
			$w, $h, $oldWidth, $oldHeight
		);

		$this->_destroy();
		$this->_image = $image;
		return $this;
	}

	/**
		Method: crop

		Extracts a region of the image.

		Parameters:
			$width - (int) The width of the crop.
			$height - (int) The height of the crop.
			$x - (int) The X coordinate of the cropped region's top left corner.
			$y - (int) The Y coordinate of the cropped region's top left corner.

		Returns:
			self
	*/
	public function crop( $x, $y, $width, $height )
	{
		$oldWidth  = $this->width();
		$oldHeight = $this->height();

		$image = $this->_create( $width, $height );
		imagecopyresampled(
			$image, $this->_image,
			0, 0, $x, $y,
			$width, $height, $oldWidth, $oldHeight
		);

		$this->_destroy();
		$this->_image = $image;
		return $this;
	}

	/**
		Method: rotate

		Rotates the image.

		Parameters:
			$angle - (float) The number of degrees to rotate the image.
			$background - (optional) (int) The background pixel.

		Returns:
			self
	*/
	public function rotate( $angle, $background = null )
	{
		if ( isset( $background ) )
		{
			$color = $this->_allocate( $background );
		}
		else
		{
			$color = imagecolortransparent( $this->_image );
		}

		$this->_image = imagerotate( $this->_image, $angle, $color );
		return $this;
	}

	/**
		Method: fill

		Flood fill.

		Parameters:
			$x - (int) x-coordinate of start point.
			$y - (int) y-coordinate of start point.
			$color - (int) The fill color.

		Returns:
			self
	*/
	public function fill( $x, $y, $color )
	{
		$color = $this->_allocate( $color );
		imagefill( $this->_image, $x, $y, $color );
		return $this;
	}

	/**
		Method: background

		Set the background color.

		Parameters:
			$background - (optional) (int) The image background.
	*/
	public function background( $background = null )
	{
		$this->_allocate( $background );
		return $this->_property( '_background', $background );
	}

	/**
		Method: fillColor

		Sets the fill color to be used for drawing filled objects.

		Parameters:
			$fillColor - (int) The fill color.

		Returns:
			self
	*/
	public function fillColor( $fillColor = null )
	{
		return $this->_property( '_fillColor', $fillColor );
	}

	/**
		Method: strokeColor

		Sets the color used for stroking object outlines.

		Parameters:
			$strokeColor - (int) The stroke color.

		Returns:
			self
	*/
	public function strokeColor( $strokeColor = null )
	{
		return $this->_property( '_strokeColor', $strokeColor );
	}

	/**
		Method: strokeWidth

		Sets the width of the stroke used to draw object outlines.

		Parameters:
			$strokeWidth - (int) The stroke width.

		Returns:
			self
	*/
	public function strokeWidth( $strokeWidth = null )
	{
		return $this->_property( '_strokeWidth', $strokeWidth );
	}

	/**
		Method: font

		Sets the fully-specified font to use when annotating with text.

		Parameters:
			$font - (string) The font name.

		Returns:
			self
	*/
	public function font( $font = null )
	{
		return $this->_property( '_font', $font );
	}

	/**
		Method: fontSize

		Sets the font size to use when annotating with text.

		Parameters:
			$fontSize - (string) The font size.

		Returns:
			self
	*/
	public function fontSize( $fontSize = null )
	{
		return $this->_property( '_fontSize', $fontSize );
	}

	/**
		Method: transparent

		Define a color as transparent.

		Parameters:
			$color - (int) A color identifier.

		Returns:
			self
	*/
	public function transparent( $color = null )
	{
		if ( isset( $color ) )
		{
			imagecolortransparent( $this->_image, $color );
		}
		else
		{
			return imagecolortransparent( $this->_image );
		}
		return $this;
	}

	/**
		Method: colorAt

		Get the index of the color of a pixel.

		Parameters:
			$x - (int) Point's x coordinate.
			$y - (int) Point's y coordinate.

		Returns:
			self
	*/
	public function colorAt( $x, $y )
	{
		return imagecolorat( $this->_image, $x, $y );
	}

	/**
		Method: point

		Draws a point.

		Parameters:
			$x - (int) Point's x coordinate.
			$y - (int) Point's y coordinate.

		Returns:
			self
	*/
	public function point( $x, $y )
	{
		imagesetpixel( $this->_image, $x, $y, $this->_strokeColor );
		return $this;
	}

	/**
		Method: line

		Draws a line.

		Parameters:
			$sx - (int) Starting x coordinate.
			$sy - (int) Starting y coordinate.
			$ex - (int) Ending x coordinate.
			$ey - (int) Ending y coordinate.

		Returns:
			self
	*/
	public function line( $sx, $sy, $ex, $ey )
	{
		imageline( $this->_image, $sx, $sy, $ex, $ey, $this->_strokeColor );
		return $this;
	}

	/**
		Method: rectangle

		Draws a rectangle.

		Parameters:
			$x1 - (int) x coordinate of the top left corner.
			$y1 - (int) y coordinate of the top left corner.
			$x2 - (int) x coordinate of the bottom right corner.
			$y2 - (int) y coordinate of the bottom right corner.

		Returns:
			self
	*/
	public function rectangle( $x1, $y1, $x2, $y2 )
	{
		imagefilledrectangle(
			$this->_image,
			$x1, $y1, $x2, $y2,
			$this->_fillColor
		);
		imagerectangle(
			$this->_image,
			$x1, $y1, $x2, $y2,
			$this->_strokeColor
		);
		return $this;
	}

	/**
		Method: text

		Draws a text.

		Parameters:
			$x - (int) The x coordinate where text is drawn.
			$y - (int) The y coordinate where text is drawn.
			$text - (string) The text to draw on the image.
			$angle - (optional) (float) The angle.

		Returns:
			self
	*/
	public function text( $x, $y, $text, $angle = 0 )
	{
		imagefttext(
			$this->_image,
			$this->_fontSize,
			$angle, $x, $y,
			$this->_strokeColor,
			$this->_font,
			$text
		);
		return $this;
	}

	/**
		Method: filename

		Set the image file name.

		Parameters:
			$filename - (optional) (string) The image file name.
	*/
	public function filename( $filename = null )
	{
		return $this->_property( '_filename', $filename );
	}

	/**
		Method: quality

		Set the image quality.

		Parameters:
			$quality - (optional) (int) The image quality (0 to 100).
	*/
	public function quality( $quality = null )
	{
		return $this->_property( '_quality', $quality );
	}

	/**
		Method: compression

		Set the image compression.

		Parameters:
			$compression - (optional) (int) The image compression (0 to 9).
	*/
	public function compression( $compression = null )
	{
		return $this->_property( '_compression', $compression );
	}

	/**
		Method: save

		Save the image in a file.

		Parameters:
			$filename - (optional) (string) A file name.

		Returns:
			self
	*/
	public function save( $filename = null )
	{
		if ( isset( $filename ) )
		{
			$this->_filename = $filename;
		}

		if ( !isset( $this->_filename ) )
		{
			throw new Exception( 'File name not specified' );
		}

		$type = pathinfo( $this->_filename, PATHINFO_EXTENSION );

		switch ( strtolower( $type ) )
		{
			case 'jpeg':
			case 'jpg':
				imagejpeg( $this->_image, $this->_filename, $this->_quality );
				break;
			case 'gif':
				imagegif( $this->_image, $this->_filename );
				break;
			case 'png':
			default:
				imagepng( $this->_image, $this->_filename, $this->_compression );
				break;
		}
		return $this;
	}

	/**
		Method: rgb

		Return a RGB color identifier.

		Parameters:
			$red - (int) Value of red component.
			$green - (int) Value of green component.
			$blue - (int) Value of blue component.

		Returns:
			int
	*/
	public static function rgb( $red, $green, $blue )
	{
		return self::rgba( $red, $green, $blue, 0 );
	}

	/**
		Method: rgba

		Return a RGBA color identifier.

		Parameters:
			$red - (int) Value of red component.
			$green - (int) Value of green component.
			$blue - (int) Value of blue component.
			$alpha - (int|float) An int between 0 and 127.
				0 indicates completely opaque while 127 indicates completely transparent.

		Returns:
			int
	*/
	public static function rgba( $red, $green, $blue, $alpha )
	{
		return (
			( $alpha & 0xff ) << 24 |
			( $red   & 0xff ) << 16 |
			( $green & 0xff ) << 8 |
			( $blue  & 0xff )
		);
	}

	/**
		Method: red

		Return value of red component.

		Parameters:
			$color - (int) A color.

		Returns:
			int
	*/
	public static function red( $color )
	{
		return ( 0xff & ( $color >> 16 ) );
	}

	/**
		Method: green

		Return value of green component.

		Parameters:
			$color - (int) A color.

		Returns:
			int
	*/
	public static function green( $color )
	{
		return ( 0xff & ( $color >> 8 ) );
	}

	/**
		Method: blue

		Return value of blue component.

		Parameters:
			$color - (int) A color.

		Returns:
			int
	*/
	public static function blue( $color )
	{
		return ( 0xff & $color );
	}

	/**
		Method: alpha

		Return value of alpha component.

		Parameters:
			$color - (int) A color.

		Returns:
			int
	*/
	public static function alpha( $color )
	{
		return ( 0xff & ( $color >> 24 ) );
	}

	// Output.
	public function __toString()
	{
		ob_start();
		imagepng( $this->_image );
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}

	// Output.
	public function _allocate( $color, $image = null )
	{
		isset( $image ) or $image = $this->_image;

		// Detect alpha value
		if ( self::alpha( $color ) )
		{
			return imagecolorallocatealpha(
				$image,
				self::red( $color ),
				self::green( $color ),
				self::blue( $color ),
				self::alpha( $color )
			);
		}
		else
		{
			return imagecolorallocate(
				$image,
				self::red( $color ),
				self::green( $color ),
				self::blue( $color )
			);
		}
	}

	// Open a file.
	protected function _load( $filename )
	{
		if ( !file_exists( $filename ) )
		{
			throw new Exception( 'File not found' );
		}

		$size = getimagesize( $filename );

		switch ( $size[ 2 ] )
		{
			case IMAGETYPE_PNG:
				$this->_image = imagecreatefrompng( $filename );
				break;
			case IMAGETYPE_JPEG:
				$this->_image = imagecreatefromjpeg( $filename );
				break;
			case IMAGETYPE_GIF:
				$this->_image = imagecreatefromgif( $filename );
				break;
			default:
				throw new Exception( "Invalid image format: '{$size[ 'mime' ]}'" );
		}
		imagealphablending( $this->_image, true );
		imagesavealpha( $this->_image, true );
		return $this->_image;
	}

	// Create a new image.
	protected function _create( $width, $height )
	{
		$image = imagecreatetruecolor( $width, $height );
		imagealphablending( $image, true );
		imagesavealpha( $image, true );
		$background = $this->background();
		if ( isset( $background ) )
		{
			$this->_allocate( $background, $image );
			imagefill( $image, 0, 0, $background );
		}
		return $image;
	}

	// Destroy the image.
	protected function _destroy()
	{
		imagedestroy( $this->_image );
	}

	// Retrieve or store a property.
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
