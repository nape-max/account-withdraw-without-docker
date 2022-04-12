<?php

declare(strict_types=1);

session_start();
session_write_close();

require_once("./autoload.php");
require_once("./error_handler.php");

use Route\Router;
use Route\Request;

Router::start(new Request());
