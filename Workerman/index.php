<?php

use Workerman\Worker;

require_once __DIR__ . '/Autoloader.php';

define("WWW_ROOT", '/data/www/HongMi-PHP/');

define("SWOOLE_CGI", 1);

//const Unset_OnRequest_Done_Constant_Names = ['UID', '__INFO__', 'MODULE_NAME', 'MODULE_PATH', 'CONTROLLER_NAME',
 //   'ACTION_NAME', 'APP_ENV', 'LOG_PATH_PREFIX', 'APP_DEBUG', 'APP_PATH'];

$web_server = new \Workerman\TPWebServer("http://0.0.0.0:8080");
$web_server->count = 16;
$web_server->addRoot('yqds.dev', WWW_ROOT);

Worker::runAll();