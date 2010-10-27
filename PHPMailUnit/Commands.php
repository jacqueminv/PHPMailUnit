<?php

/**
 * A minimum implementation must contain these SMTP "verbs":
 *
 *   HELO
 *   MAIL
 *   RCPT
 *   DATA
 *   RSET
 *   NOOP
 *   QUIT
 *
 */

function data($line) {
    $data = trim($line);
    $results = explode(" ", $data, 2);
    return $results;
}

abstract class Command {

   abstract public function process(PHPUnitMail $mail, $command);

}

class HeloCommand extends Command {
    
    public function process(PHPUnitMail $mail, $command) {
        $command = data($command);
        if($command[0] == "HELO") {
            if(count($command) == 1) {
                return "502 domain missing" . CRLF;
            } else {
                return sprintf("250 %s %s", $command[1], CRLF);
            }
        }
        return "";
    }

}

class EhloCommand extends Command {

    public function process(PHPUnitMail $mail, $command) {
        $command = data($command);
        if($command[0] == "EHLO") {
            return "502 Command not implemented" . CRLF;
        }
        return "";
    }

}

class MailCommand extends Command {

    public function process(PHPUnitMail $mail, $command) {
        $command = data($command);
        if($command[0] == "MAIL") {
            $matched = preg_match("/<(.*)>/", $command[1], $from);

            if($matched !== 0) {
                $from = $from[1];
            } else {
                return "503 Bad request (sender not well set)" . CRLF;
            }

            $mail->from = $from;

            if(DEBUG) {
                echo sprintf("Matched FROM address: %s%s", $from, CRLF);
            }

            return "250 OK" . CRLF;
        }
        return "";
    }

}

class RCPTCommand extends Command {

    public function process(PHPUnitMail $mail, $command) {
        $command = data($command);
        if($command[0] == "RCPT") {
            if(preg_match("/(.*):<(.*)>/", $command[1], $rcpt) == 1) {
                if(strtoupper($rcpt[1]) == "TO") {
                        $mail->to[] = $rcpt[2];

                    if(DEBUG) {
                        echo sprintf("Matched recipient address: %s - %s%s"
                                    , $rcpt[1]
                                    , $rcpt[2]
                                    , CRLF);
                    }

                    return "250 OK" . CRLF;
                }
                
            } else {

                return "503 Bad request" . CRLF;
            }
        }
        
        return "";
    }

}

class DataCommand extends Command {

    const ENDING = ".";

    public function process(PHPUnitMail $mail, $command) {
        if($mail->data_processing) {

            if(($pos = strpos($command, "Subject: ")) !== false) {

                //strangely, it appears that the first carriage return is not
                //always correctly caught
                $mail->subject = substr($command, ($pos + strlen("Subject: "))
                                        , (strpos($command, "\n", $pos) - $pos));
                if(strpos($mail->subject, "\n") !== false) {
                    $mail->subject = substr($mail->subject
                                            , 0
                                            , (strpos($mail->subject, "\n")));
                }

                if(DEBUG) {
                    echo sprintf("Matched subject: [%s]%s", $mail->subject, CRLF);
                }
            }

            $mail->content .= $command;

            if(strrpos($command, self::ENDING)
                === (strlen($command) - strlen(self::ENDING))) {

                $mail->content = substr($mail->content
                                        , 0
                                        , strlen($mail->content) - strlen(self::ENDING));

                return "250 OK" . CRLF;
            }

        } else {
            $command = data($command);
            if($command[0] == "DATA") {
                $mail->data_processing = true;
                return "354 Start mail input; end with <CRLF>.<CRLF>" . CRLF;
            }
        }
        
        return "";
    }
}

class RSETCommand extends Command {

    public function  process(PHPUnitMail $mail, $command) {
        $command = data($command);
        if($command[0] == "RSET") {
            $mail->reset();
        }
        return "";
    }

}