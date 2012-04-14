<?php

define( 'APP_PATH', realpath( 'application' ) );

require_once 'system/index.php';

Core::run( 'public', 'default' );

