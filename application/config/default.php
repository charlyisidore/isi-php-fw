<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

/*
	Routes
*/

Route::factory( '/', array( 'IndexController', '__invoke' ) );

Route::factory( '/:who', array( 'IndexController', '__invoke' ) )
	->parameters( array( ':who' => '[a-zA-Z0-9-_]+' ) );

Route::factory( '/json/:who', array( 'IndexController', 'json' ) )
	->parameters( array( ':who' => '[a-zA-Z0-9-_]+' ) );

Route::factory( 404, array( 'IndexController', '__404' ) );

/*
	Error handler
*/

//Error::initialize();
//Error::register( 'error_handler_function' );

/*
	Session
*/

//Session::start();

/*
	User management
*/

//User::initialize();

