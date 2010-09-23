<?php

function data($line) {
    $data = trim($line);
    $results = explode(" ", $data, 2);
    return $results;
}

abstract class Command {

   abstract public function process(PHPUnitMail $PHPUnitMail, $command);

}

class HeloCommand extends Command {
    
    public function process(PHPUnitMail $PHPUnitMail, $command) {
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

    public function process(PHPUnitMail $PHPUnitMail, $command) {
        $command = data($command);
        if($command[0] == "EHLO") {
            return "502 Command not implemented" . CRLF;
        }
        return "";
    }

}

class MailCommand extends Command {

    public function process(PHPUnitMail $PHPUnitMail, $command) {
        $command = data($command);
        if($command[0] == "MAIL") {
            $matched = preg_match("/<(.*)>/", $command[1], $from);

            if($matched !== 0) {
                $from = $from[1];
            } else {
                return "503 Bad request (sender not well set)" . CRLF;
            }

            $PHPUnitMail->from = $from;

            if(DEBUG) {
                echo sprintf("Matched FROM address: %s%s", $from, CRLF);
            }

            return "250 OK" . CRLF;
        }
        return "";
    }

}

class RCPTCommand extends Command {

    public function process(PHPUnitMail $PHPUnitMail, $command) {
        $command = data($command);
        if($command[0] == "RCPT") {
            if(preg_match("/(.*):<(.*)>/", $command[1], $rcpt) == 1) {
                if(strtoupper($rcpt[1]) == "TO") {
                        $PHPUnitMail->to[] = $rcpt[2];

			if(DEBUG) {
			    echo sprintf("Matched recipient address: %s - %s%s", $rcpt[1], $rcpt[2], CRLF);
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

    public function process(PHPUnitMail $PHPUnitMail, $command) {
        if($PHPUnitMail->data_processing) {
            if(($pos = strpos($command, "Subject: ")) !== false) {
                $PHPUnitMail->subject = substr($command, $pos + strlen("Subject: "));
                if(DEBUG) {
                    echo sprintf("Matched subject: %s%s", substr($command, $pos), CRLF);
                }
            } elseif(!$PHPUnitMail->data_body_processing && strlen($command) == 0) {
                if(DEBUG) {
                    echo "Body processing started" . CRLF;
                }
                $PHPUnitMail->data_body_processing = true;
            } elseif($PHPUnitMail->data_body_processing) {
                if($command == ".") {
                    $PHPUnitMail->data_body_processing = false;
                    if(DEBUG) {
                        echo "Body processing ended" . CRLF;
                    }
                } else {
                    $PHPUnitMail->content .= $command . CRLF;
                }
            } else {
                $PHPUnitMail->content = substr($PHPUnitMail->content, 0, strlen($PHPUnitMail->content) - 2);
                return "250 OK" . CRLF;
            }

        } else {
            $command = data($command);
            if($command[0] == "DATA") {
                $PHPUnitMail->data_processing = true;
                return "354 Start mail input; end with <CRLF>.<CRLF>" . CRLF;
            }
        }
        
        return "";
    }
    
}
?>