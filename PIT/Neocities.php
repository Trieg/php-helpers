<?php
/*
 |  Neocities   A basic implementation of the Neocities API in PHP.
 |  @file       ./PIT/Neocities.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    CC0 - Public Domain
 |  @copyright  Copyright © 2018 SamBrishes, pytesNET <pytes@gmx.net>
 */

    namespace PIT;

    class Neocities{
        /*
         |  GLOBAL VARs
         */
        protected $url = "https://neocities.org/";
        protected $post = NULL;
        protected $token = NULL;
        protected $method = NULL;

        /*
         |  CURRENT VARs
         */
        public $lastCall;
        public $lastResponse;
        public $lastResponseCode;

        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         |
         |  @param  string  The respective site token or NULL.
         */
        public function __construct($token = NULL){
            if(!empty($token) && is_string($token)){
                $this->token = $token;
            }
        }

        /*
         |  INTERNAL :: SEND TO NEOCITIES
         |  @since  0.1.0
         |
         |  @param  multi   Additional URL arguments as STRING or ARRAY.
         |
         |  @return multi   The received content on success, FALSE on failure.
         */
        private function send($args = NULL){
            if(empty($this->url) || empty($this->method)){
                $this->lastResponse = NULL;
                $this->lastResponseCode = 0;
                return false;
            }

            // Build URL
            $url = $this->url . $this->method;
            if(!empty($args)){
                if(is_array($args)){
                    $args = http_build_query($args);
                }
                if(strpos($args, "?") === 0 || strpos($args, "&") === 0){
                    $args = substr($args, 1);
                }
                $url .= (strpos($url, "?") == false)? "?{$args}": "&{$args}";
            }

            // cURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            // Set Post
            if(!empty($this->post)){
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $this->post);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            }

            // Set Token
            if(!empty($this->token) && is_string($this->token)){
                $auth = "Authorization: Bearer " . $this->token;
                if(!empty($this->post)){
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data", $auth));
                } else {
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", $auth));
                }
            }

            // Call
            $content = curl_exec($curl);
            $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // Convert
            if(!empty($content) && is_string($content)){
                $temp = json_decode($content, true);
                if(is_array($temp)){
                    $content = $temp;
                }
            }

            // Store
            $this->lastCall = $url;
            $this->lastResponse = $content;
            $this->lastResponseCode = $response;

            // Result
            if($response !== 200){
                return false;
            }
            return $content;
        }

        /*
         |  API :: UPLOAD FILES
         |  @since  0.1.0
         |
         |  @param  array   A site_file.ext => local_file.ext array.
         |                  :   The site file need to contain the full path on the server.
         |                  :   The local file can either a path or a CURLFile instance.
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function neoUpload($files){
            if(empty($files) || !is_array($files)){
                return false;
            }
            $this->method = "api/upload";

            // Sanitize Data
            $this->post = array();
            foreach($files AS $path => $file){
                if(is_string($file)){
                    if(!file_exists($file) || !is_file($file)){
                        continue;
                    }
                    $file = curl_file_create($file);
                }
                if(!is_a($file, "CURLFile")){
                    continue;
                }
                $this->post[$path] = $file;
            }

            // Send Data
            if(($response = $this->send()) == false){
                return false;
            }
            return $this->lastResponseCode == 200;
        }

        /*
         |  API :: DELETE FILES
         |  @since  0.1.0
         |
         |  @param  multi   A single filename (with the complete site path) as STRING, multiple as array.
         |
         |  @return bool    TRUE on success, FALSE on failure.
         */
        public function neoDelete($filenames){
            if(empty($filenames)){
                return false;
            }
            $this->method = "api/delete";

            // Sanitize Data
            if(is_string($filenames)){
                $filenames = array($filenames);
            }

            $this->post = "";
            foreach($filenames AS &$file){
                if(!is_string($file) || $file == "index.html"){
                    $file = NULL;
                }
            }
            $files = array_filter($filenames);
            if(empty($files)){
                return false;
            }
            $this->post = "filenames[]=" . implode("&filenames[]=", $files);

            // Send Data
            if(($response = $this->send()) == false){
                return false;
            }
            return $this->lastResponseCode == 200;
        }

        /*
         |  API :: LIST DIR / FILES
         |  @since  0.1.0
         |
         |  @param  string  A site path or an empty string for the root path.
         |
         |  @return multi   An array filled with files and directory, or FALSE on failure.
         */
        public function neoList($path = ""){
            $this->post = array();
            $this->method = "api/list";

            // Send Data
            if(empty($path)){
                if(($response = $this->send()) == false){
                    return false;
                }
            } else {
                if(($response = $this->send(array("path" => $path))) == false){
                    return false;
                }
            }

            // Verify Data
            if(!isset($response["result"], $response["files"]) || $response["result"] !== "success"){
                return false;
            }
            return $response["files"];
        }

        /*
         |  API :: GET SITE INFO
         |  @since  0.1.0
         |
         |  @param  string  The sitename where you looking for or NULL for your site.
         |
         |  @return multi   An array with all provieded neocities data, or false on failure.
         |
         */
        public function neoInfo($sitename = NULL){
            $this->post = array();
            $this->method = "api/info";

            // Send Data
            if(empty($sitename)){
                if(($response = $this->send()) == false){
                    return false;
                }
            } else {
                if(($response = $this->send(array("sitename" => $sitename))) == false){
                    return false;
                }
            }

            // Verify Data
            if(!isset($response["result"], $response["info"]) || $response["result"] !== "success"){
                return false;
            }
            return $response["info"];
        }

        /*
         |  API :: GET API KEY
         |  @since  0.1.0
         |
         |  @param  string  The used username, which is usually the site name.
         |  @param  string  The used password for your page.
         |  @param  bool    TRUE to store the API key for furhter requests, FALSE to just return ut.
         |
         |  @return multi   The key as STRING on success, FALSE on failure.
         */
        public function neoKey($user, $pass, $store = true){
            if(empty($user) || empty($pass)){
                return false;
            }

            // Set Data
            $this->url = "https://{$user}:{$pass}@neocities.org/";
            $this->post = array();
            $this->method = "api/key";

            // Send Data
            if(($response = $this->send()) === false){
                $this->url = "https://neocities.org/";
                return false;
            }
            $this->url = "https://neocities.org/";

            // Verify Data
            if(!isset($response["result"], $response["api_key"]) || $response["result"] !== "success"){
                return false;
            }
            if($store){
                $this->token = $response["api_key"];
            }
            return $response["api_key"];
        }
    }
