<?php

require_once dirname(__FILE__) . '/config.inc';
require_once dirname(__FILE__) . '/Mail.php';

class PHPMailUnit {

    public static function setUp() {
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . "server.php";
        exec(sprintf("%s %s > %s 2>&1 &", PHP, $path, LOG_DIR . DIRECTORY_SEPARATOR . "SERVER"), $output, $status);

	//pretty ugly hack. find a way to be sure that the server is ok
	sleep(2);
    }

    public static function tearDown() {
        if (($sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            error_log("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
        }

        if (@socket_bind($sock, ADDRESS) === false) {
            error_log("socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)));
        }

        if (@socket_connect($sock, ADDRESS, PORT) === false) {
            error_log("socket_connect() failed: reason: " . socket_strerror(socket_last_error($sock)));
        }
        if (@socket_write($sock, "SHUTDOWN" . CRLF) === false) {
            error_log("socket_write() failed: reason: " . socket_strerror(socket_last_error($sock)));
        }

        @unlink(LOG_DIR . DIRECTORY_SEPARATOR . "emails");
    }

    public static function getLastMail() {
    	$email_path = LOG_DIR . DIRECTORY_SEPARATOR . "emails";
        if(file_exists($email_path)) {
            $content = file_get_contents($email_path);
            $mail = unserialize($content);
            return $mail;
        } else {
	    trigger_error("No mail found");
            return null;	    
	}
    }

}

?>
