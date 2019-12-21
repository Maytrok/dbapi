<?php

namespace dbapi\bin;

use dbapi\tools\App;
use Monolog\Logger;


// Create the logger
App::$looger = new Logger('dbapi_default_logger');

App::$looger->pushHandler(new \Monolog\Handler\NullHandler());
