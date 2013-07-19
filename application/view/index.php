<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Hello, <?php echo $who; ?>!</title>
	</head>
	<body>
		<h1>Hello, <?php echo $who; ?>!</h1>
		<p>Try : <a href="<?php echo Url::page( '/you' ); ?>"><?php echo Url::page( '/you' ); ?></a></p>
		<p>Try : <a href="<?php echo Url::page( '/json/you' ); ?>"><?php echo Url::page( '/json/you' ); ?></a></p>
	</body>
</html>
