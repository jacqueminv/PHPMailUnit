<?php

/**
 * Plain object corresponding to an email sent
 */
class PHPUnitMail {

    public $from;
    public $subject;
    public $to = array();
    public $cc = array();
    public $bcc = array();
    public $content = "";

    //internaly used properties
    public $data_processing = false;
    public $data_body_processing = false;
}

?>
