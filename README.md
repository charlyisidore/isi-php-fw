isi-php-fw
==========


Introduction
------------

**isi-php-fw** is a minimalist framework in PHP5 for lightweight applications.

This micro framework has only 6 files as kernel.
It provides minimal stuff to route requests to function/method calls.
The kernel is also extensible using auto-loaded PHP files.
Take only the extensions you need and create yours !

Version : _0.1.0_


Requirements
------------

PHP 5 >= 5.1.2


System structure
----------------

The following describes the structure of the system.

* `system/` : Root of the system
* `system/core/` : Kernel
* `system/library/` : Extensions
* `application/` : An example of application
* `static/` : An example of CSS and javascript assets


Application structure
---------------------

The following describes the structure of an application.

* `application/config/` : Configuration files
* `application/controller/` : Controllers
* `application/view/` : Templates
* `application/library/` : Local library


Tutorial
--------

We describe how to make a minimal _Hello world_ application.

1. Create an empty directory, this will be our root directory.

2. Copy the `system/` folder in the root directory.

3. We need to make the `myapp/` application structure. Create the following folders in the root directory :
  * `myapp/`
  * `myapp/config/`
  * `myapp/controller/`
  * `myapp/view/`
  * `myapp/library/`

4. To run the application, we have to make a callable controller. Create the file `myapp/controller/indexcontroller.php` and write :

		<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );
		//
		class IndexController
		{
			public function go( $name = '' )
			{
				return "Hello $name !\n";
			}
		}

5. Then we say which kind of urls goes to our controller, particularly `http://site.com/index.php/<nickname>`. Create the file `myapp/config/myconfig.php` and write :

		<?php defined( 'SECURITY_CONST' ) or exit( 'Access Denied' );
		//
		// Match /index.php/<name> and run IndexController->go(<name>)
		Route::factory( '/:name', array( 'IndexController', 'go' ) )
			->parameters( array( ':name' => '([a-zA-Z]\w*)?' ) );

6. Finally, create a bootstrap file _index.php_.

		<?php
		//
		require_once 'system/launcher.php';
		Launcher::run( 'myapp', 'myconfig' );

7. Run your browser on `http://site.com/index.php` and `http://site.com/index.php/world`. It should display a text message.


Optional libraries included
---------------------------

* [Services\_JSON v1.0.3](http://pear.php.net/package/Services_JSON) : `JSON.php`
* [Mootools Core v1.4.5](http://www.mootools.net/) : `static/js/mootools-core-1.4.5-full-nocompat-yc.js`
* [Pure v0.2.1](http://purecss.io/) : `static/css/pure-min.css`

_Services\_JSON_ provides _json\_encode_ for PHP 5 < 5.2.0.
You can also delete it if you run with PHP 5 >= 5.2.0.


Natural Docs documentation generation
-------------------------------------

In Natural Docs languages file (Languages.txt), please add:

		Alter Language: PHP

			Block Comment: /** */


Authors
-------

* Charly Lersteau


License
-------

**isi-php-fw** is a minimalist framework in PHP5 for lightweight applications.

Copyright (C) 2013  Charly Lersteau

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

