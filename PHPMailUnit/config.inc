<?php

//command line to execute php
define('PHP', 'php');

//display debugging messages when `True`
define('DEBUG', true);
define("CRLF", "\r\n");
define("LOG_DIR", dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "log");

if(!file_exists(LOG_DIR)) {
    if(!mkdir(LOG_DIR)) {
        trigger_error("Log directory does not exist and it's impossible to create it");
	exit -1;
    }
}

//smtp server config
define("ADDRESS", "localhost");
define("PORT", 3535);

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush(true);

?>
