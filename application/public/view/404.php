<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Page not found | 404</title>
		<style type="text/css">
			html, body
			{
				margin: 0;
				padding: 0;
				font-family: sans-serif;
				font-size: 20px;
			}
			h1
			{
				font-size: 42px;
			}
			a
			{
				font-size: 30px;
				color: #33f;
				font-weight: bold;
				text-decoration: none;
			}
			a:hover
			{
				color: #66f;
			}
			#lost
			{
				width: 600px;
				text-align: center;
				margin: -300px auto 0 auto;
				z-index: 9;
			}
			#clouds
			{
				overflow: hidden;
				padding: 100px 0 0 0;
				background-color: #c9dbe9;
				background-image: linear-gradient(top, #c9dbe9 0%, #fff 100%);
				background-image: -moz-linear-gradient(top, #c9dbe9 0%, #fff 100%);
				background-image: -webkit-linear-gradient(top, #c9dbe9 0%, #fff 100%);
			}
			.cloud
			{
				position: relative; 
				width: 200px;
				height: 60px;
				background: #fff;
				border-radius: 200px;
				-moz-border-radius: 200px;
				-webkit-border-radius: 200px;
			}
			.cloud:before, .cloud:after
			{
				content: '';
				position: absolute;
				background: #fff;
				width: 100px;
				height: 80px;
				top: -15px;
				left: 10px;
				border-radius: 100px;
				-moz-border-radius: 100px;
				-webkit-border-radius: 100px;
				transform: rotate(30deg);
				-moz-transform: rotate(30deg);
				-webkit-transform: rotate(30deg);
			}
			.cloud:after
			{
				width: 120px;
				height: 120px;
				top: -55px;
				right: 15px;
				left: auto;
			}
			.x1
			{
				-moz-animation: moveclouds 15s linear infinite;
				-webkit-animation: moveclouds 15s linear infinite;
				-o-animation: moveclouds 15s linear infinite;
			}
			.x2
			{
				left: 200px;
				transform: scale(0.6);
				-moz-transform: scale(0.6);
				-webkit-transform: scale(0.6);
				opacity: 0.6;
				-moz-animation: moveclouds 25s linear infinite;
				-webkit-animation: moveclouds 25s linear infinite;
				-o-animation: moveclouds 25s linear infinite;
			}
			.x3
			{
				left: -250px;
				top: -200px;
				transform: scale(0.8);
				-moz-transform: scale(0.8);
				-webkit-transform: scale(0.8);
				opacity: 0.8;
				-moz-animation: moveclouds 20s linear infinite;
				-webkit-animation: moveclouds 20s linear infinite;
				-o-animation: moveclouds 20s linear infinite;
			}
			.x4
			{
				left: 470px;
				top: -250px;
				transform: scale(0.75);
				-moz-transform: scale(0.75);
				-webkit-transform: scale(0.75);
				opacity: 0.75;
				-moz-animation: moveclouds 18s linear infinite;
				-webkit-animation: moveclouds 18s linear infinite;
				-o-animation: moveclouds 18s linear infinite;
			}
			.x5
			{
				left: -150px;
				top: -150px;
				transform: scale(0.8);
				-webkit-transform: scale(0.8);
				-moz-transform: scale(0.8);
				opacity: 0.8;
				-moz-animation: moveclouds 20s linear infinite;
				-webkit-animation: moveclouds 20s linear infinite;
				-o-animation: moveclouds 20s linear infinite;
			}
			@-moz-keyframes moveclouds
			{
				from { margin-left: 1600px; }
				to   { margin-left: -600px; }
			}
			@-webkit-keyframes moveclouds
			{
				from { margin-left: 1600px; }
				to   { margin-left: -600px; }
			}
			@-o-keyframes moveclouds
			{
				from { margin-left: 1600px; }
				to   { margin-left: -600px; }
			}
		</style>
	</head>
	<body>
		<div id="clouds">
			<div class="cloud x1"></div>
			<div class="cloud x2"></div>
			<div class="cloud x3"></div>
			<div class="cloud x4"></div>
			<div class="cloud x5"></div>
		</div>
		<div id="lost">
			<h1>Oops !</h1>
			<p>Sorry, we can't find <strong><?php echo htmlspecialchars( $path ); ?></strong></p>
			<p><a href="<?php echo Url::base(); ?>">Home</a></p>
		</div>
	</body>
</html>
