# isi-php-fw


### Introduction

**isi-php-fw** is a minimalist framework in PHP5 for lightweight applications.

This micro framework has only 6 files as kernel.
It provides minimal stuff to route requests to function/method calls.
The kernel is also extensible using auto-loaded PHP files.
Take only the extensions you need and create yours !


### Requirements

PHP 5.1+


### System structure

The following describes the structure of the system.

* `system/` : Root of the system
* `system/core/` : Kernel
* `system/library/` : Extensions
* `application/` : An example of application
* `static/` : An example of CSS and javascript assets


### Application structure

The following describes the structure of an application.

* `application/config/` : Configuration files
* `application/controller/` : Controllers
* `application/view/` : Templates
* `application/library/` : Local library


### Tutorial

We describe how to make a minimal _Hello world_ application.

1. Create an empty directory, this will be our root directory.

* Copy the `system/` folder in the root directory.

* We need to make the `myapp/` application structure. Create the following folders in the root directory :

	* `myapp/`
	* `myapp/config/`
	* `myapp/controller/`
	* `myapp/view/`
	* `myapp/library/`

* To run the application, we have to make a callable controller. Create the file `myapp/controller/indexcontroller.php` and write :

		<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );
		//
		class IndexController
		{
			public function go( $name = '' )
			{
				return "Hello $name !\n";
			}
		}

* Then we say which kind of urls goes to our controller, particularly `http://site.com/index.php/<nickname>`. Create the file `myapp/config/myconfig.php` and write :

		<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );
		//
		// Match /index.php/<name> and run IndexController->go(<name>)
		Route::factory( '/:name', array( 'IndexController', 'go' ) )
			->parameters( array( ':name' => '([a-zA-Z]\w*)?' ) );

* Finally, create a bootstrap file _index.php_.

		<?php
		//
		require_once 'system/launcher.php';
		Launcher::run( 'myapp', 'myconfig' );

* Run your browser on `http://site.com/index.php` and `http://site.com/index.php/world`. It should display a text message.


### Natural Docs documentation generation

In Natural Docs languages file (Languages.txt), please add:

		Alter Language: PHP

			Block Comment: /** */

