<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/**
	Class: PHPView

	A minimal PHP template engine.

	Author:
		Charly Lersteau

	Date:
		2013-03-23

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
		extract( $this->get() );
		ob_start( null );
		include $this->file();
		$data = ob_get_contents();
		ob_end_clean();
		return (string)$data;
	}
}

