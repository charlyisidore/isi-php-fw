<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );

Route::factory( '/', array( 'IndexController', '__invoke' ) );
Route::factory( 404, array( 'IndexController', 'notfound' ) );

