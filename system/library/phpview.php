<?php if ( !defined( 'SECURITY_CONST' ) ) die( 'Access Denied' );

/**
	Class: PHPView

	A minimal template engine.

	Author:
		Charly Lersteau

	Date:
		2012-01-17

	Extends:
		<View>

	Example:
		>	<div class="article">
		>		<h2><?php echo $title; ?></h2>
		>		<p class="author">Date: <?php echo $date; ?></p>
		>		<p class="date">Author: <?php echo $author; ?></p>
		>		<div class="content"><?php echo $content; ?></div>
		>	</div>
*/
class PHPView extends View
{
	/**
		Method: __toString
	*/
	public function __toString()
	{
		extract( $this->_variables );

		ob_start();
		include $this->_file;
		return ob_get_clean();
	}
}

