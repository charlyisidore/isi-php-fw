<?php

require_once 'system/launcher.php';

// Provides json_encode for PHP<5.2, you can delete it otherwise
require_once 'JSON.php';

Launcher::run( 'application', 'default' );

