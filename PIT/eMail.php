<?php
/*
 |  eMail       A really basic eMail helper function.
 |  @file       ./PIT/eMail.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <info@pytes.net>
 */

    namespace PIT;

    class eMail{
        /*
         |  HELPER :: GENERATE UTF-8 VALID STRING
         |  @since  0.1.0
         */
        static public function formatUTF8($data, $email = false){
            if($email === true){
                if(strpos($email, "<") !== false){
                    $data = explode("<", $data);
                    list($data, $mail) = $data;
                } else {
                    $data = explode("@", $data);
                    list($data, $email) = array($data[0], $data[0] . "@" . $data[1] . ">");
                }
            }

            $data = "=?UTF-8?B?" . base64_encode(trim($data)) . "?=";
            if($email && isset($mail)){
                return $data . " <" . trim($mail);
            }
            return $data;
        }

        /*
         |  SEND A NEW EMAIL
         |  @since  0.1.0
         |
         |  @param  string  The "sender" / "from" eMail address as STRING.
         |                  Use "sender@example.com" or "My Name <sender@example.com>"
         |  @param  multi   The "receiver" / "to" eMail address(es) as STRING or as ARRAY.
         |                  Separate multiple addresses with an comma (,) or pass it as ARRAY.
         |                  Use "receiver@example.com" or "My Name <receiver@example.com>"
         |  @param  string  The eMail subject as STRING.
         |  @param  string  The eMail body as STRING.
         |  @param  array   Some additional header arguments, which gets passed to the eMail:
         |                  Use it as key => value pair such as:
         |                      array("Content-Type" => "text/html; charset=utf-8")
         */
        static public function send($from, $to, $subject, $body, $headers = array()){
            $time = time();

            // Get Plain eMail
            if(stripos($from, "<") !== false){
                $mail = explode("<", $from);
                $mail = trim(trim($mail[1], ">"));
            } else {
                $mail = $from;
            }

            // Default Headers
            $header = array(
                'MIME-Version'              => '1.0',
                'Content-Type'              => 'text/html; charset=utf-8',
                'Content-Transfer-Encoding' => '8bit'
            );
            $header = array_merge($header, $headers);
            $utf8 = stripos($header["Content-Type"], "utf-8") !== false;

            // Format Headers
            if(!isset($header["From"])){
                $header["From"] = ($utf8)? self::formatUTF8($from, true): $from;
            }
            if(!isset($header["Reply-To"])){
                $header["Reply-To"] = ($utf8)? self::formatUTF8($from, true): $from;
            }
            if(!isset($header["Return-Path"])){
                $header["Return-Path"] = $mail;
            }
            if(!isset($header["Message-ID"])){
                $header["Message-ID"] = "<{$time}webmaster@{$_SERVER["SERVER_NAME"]}>";
            }
            if(!isset($header["X-Mailer"])){
                $header["X-Mailer"] = "PHP/" . phpversion();
            }

            // Prepare eMail Headers
            if(version_compare(PHP_VERSION, "7.2.0", "<")){
                foreach($header AS $key => &$value){
                    $value = "{$key}: {$value}";
                }
                $header = implode(PHP_EOL, $header);
            }

            // Create eMail Body
            if(stripos($header["Content-Type"], "text/plain") === false){
                if(strpos($body, "<!DOCTYPE") === false && strpos($body, "<html>") === false){
                    $content  = '<!DOCTYPE html>' . PHP_EOL;
                    $content .= '<html>' . PHP_EOL;
                    $content .= '    <head>' . PHP_EOL;
                    $content .= '        <meta charset="utf-8">' . PHP_EOL;
                    $content .= '        <title>' . strip_tags($subject) . '</title>' . PHP_EOL;
                    $content .= '    </head>' . PHP_EOL;
                    $content .= '    <body>'.$body.'</body>' . PHP_EOL;
                    $content .= '</html>';
                } else {
                    $content = $body;
                }
            } else {
                $content = $body;
            }

            // Send Mail
            if(is_array($to)){
                $to  = implode(", ", $to);
            }
            $subject = ($utf8)? self::formatUTF8($subject, false): strip_tags($subject);
            return mail($to, $subject, $content, $header);
        }
    }
