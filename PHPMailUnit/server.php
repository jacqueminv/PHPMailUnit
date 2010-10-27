#!/usr/bin/env php
<?php

define('VERSION', "0.5");

require_once dirname(__FILE__) . '/config.inc';
require_once dirname(__FILE__) . '/Mail.php';
require_once dirname(__FILE__) . '/Commands.php';

if (($sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    error_log("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
}

if ((@socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 10, 'usec' => 0))) === false) {
    error_log("socket_set_option() failed: reason: " . socket_strerror(socket_last_error()));
}

if (@socket_bind($sock, ADDRESS, PORT) === false) {
    error_log("socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)));
}

if (@socket_listen($sock, 5) === false) {
    error_log("socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)));
}
    
if(DEBUG) {
    echo sprintf("SMTP server initialised and listening on %s:%s%s", ADDRESS, PORT, CRLF);
}

while(true) {
    if (($msgsock = @socket_accept($sock)) === false) {
        error_log("socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)));
        break;
    }

    if(DEBUG) {
        echo sprintf("S: 220 %s PHPMailUnit server %s%s", php_uname("n") , VERSION, CRLF);
    }

    if(@socket_write($msgsock, sprintf("220 %s PHPMailUnit server %s%s", php_uname("n") , VERSION, CRLF)) === false) {
        error_log("socket_write() failed: reason: " . socket_strerror(socket_last_error($msgsock)));
    }

    $mail = new PHPUnitMail();
    $commands = array(
        new HeloCommand(),
        new EhloCommand(),
        new MailCommand(),
        new RCPTCommand(),
        new DataCommand(),
        new RSETCommand()
    );

    $data = "";
    while(true) {
        if(($data .= @socket_read($msgsock, 1024)) === false) {
            error_log("socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)));
            socket_close($msgsock);
            break 2;
        }

        if(!(ord(substr($data, strlen($data)-1, 1)) === 10
            && ord(substr($data, strlen($data)-2, 1)) === 13)) {
            continue;
        }

        $data = trim($data);
        $payload = "";
	
        if(DEBUG) {
            echo sprintf("C: %s %s", $data, CRLF);
        }

        $results = data($data);
        if($results[0] == "SHUTDOWN") {
            socket_close($msgsock);
            break 2;
        } elseif($results[0] == "QUIT") {
            $payload = "221 Bye" . CRLF;
            //persist the transaction
            $email_path = LOG_DIR . DIRECTORY_SEPARATOR . "emails";
            fopen($email_path, "w");
            $file = fopen($email_path, "w");
            fwrite($file, serialize($mail));
            fclose($file);

            if(DEBUG) {
               $ok = file_exists($email_path) ? "[OK]" : "[ERROR]";
               echo sprintf("Email persisted in %s %s%s", $email_path, $ok, CRLF);
            }
            break;
        }

        foreach ($commands as $command) {
            $payload .= $command->process($mail, $data);
        }
        $data = "";

        if(strlen($payload) > 0) {
            if(DEBUG) {
                echo sprintf("S: %s%s", $payload, CRLF);
            }

            if(@socket_write($msgsock, $payload) === false) {
                error_log("socket_write() failed: reason: " . socket_strerror(socket_last_error($msgsock)));
            }
        }
    }
    socket_close($msgsock);
}
socket_close($sock);