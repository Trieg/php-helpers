<?php
/*
 |  Gettext     Another PHP Gettext library
 |  @file       ./PIT/Gettext/GettextReader.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    1.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2013 - 2019 SamBrishes, pytesNET <pytes@gmx.net>
 */

    namespace PIT\Gettext;

    class GettextReader{
        /*
         |  INSTANCE VARs
         */
        public $error = false;
        public $files = array();
        public $locale = "en_US";
        
        protected $path = "";
        protected $config = array();
        
        /*
         |  CACHING VARs
         */
        private $file = NULL;
        private $length = NULL;
        private $handle = NULL;
        private $cursor = 0;
        
        private $bytecode = "V";
        private $bytedata = array(
            "file_format"       => 0, 
            "number"            => 0, 
            "original"          => 0, 
            "translation"       => 0, 
            "hashing_start"     => 0, 
            "hashing_offset"    => 0
        );
        private $original = array();
        private $translations = array();
        private $original_strings = array();
        private $translated_strings = array();
        
        
        /*
         |  CONSTRUCTOR
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function __construct($domain, $config, $locale = "en_US", $default = false){
            $domain = $domain;
            
            // GET REGEX CODE
            if($default){
                $regex = "^(?:{$domain}\-)?([a-z]{2,3}\_[A-Z]{2})?\.mo$";
            } else {
                $regex = "^{$domain}\-([a-z]{2,3}\_[A-Z]{2})?\.mo$";
            }
            
            // GET MO FILES
            $files = array();
            if(is_dir($config["path"])){
                if($handle = opendir($config["path"])){
                    while(($file = readdir($handle)) !== false){
                        if(!is_dir($config["path"].$file) && preg_match("#".$regex."#", $file)){
                            $key = preg_replace("#".$regex."#", "$1", $file);
                            $files[$key] = $file;
                        }
                    }
                    closedir($handle);
                }
            } else {
                $this->error = true;
                return;
            }
            if(empty($files)){
                $this->error = true;
                return;
            }
            
            // INIT CLASS
            if($this->error === false){
                $this->path = $config["path"];
                $this->config = $config;
                
                $this->files = $files;
                $this->locale = $locale;
            }
        }
        
        /*
         |  OPEN FILE
         |  @since  1.0.0
         */
        private function open_file(){
            if(isset($this->file) && !empty($this->file)){
                $this->handle = fopen($this->file, "rb");
            }
        }
        
        /*
         |  CHECK FILE
         |  @since  1.0.0
         */
        public function check_file(){
            if($this->file === NULL){
                if($this->read_file() === false){
                    $this->error = true;
                    $this->close_file();
                    return false;
                }
                $this->cache_file_start();
                $this->close_file();
            }
            return true;
        }
        
        /*
         |  READ FILE
         |  @since  1.0.0
         |  @update 1.1.0
         */
        private function read_file($locale = ""){
            if(empty($locale)){ $locale = $this->locale; }
            
            $getFile = false;
            if(array_key_exists($locale, $this->files)){
                $getFile = $this->files[$this->locale];
            }
            if($getFile === false){
                $this->error = true;
                return false;
            }
            $getFile = $this->path.$getFile;
            
            if(($handle = fopen($getFile, "rb")) === false){
                $this->error = true;
                return false;
            }
            
            $this->file = $getFile;
            $this->length = filesize($getFile);
            $this->handle = $handle;
            $this->cursor = 0;
            
            $magic = $this->read_bytes(4);
            if($magic === "\x95\x04\x12\xde"){
                $this->bytecode = "N";
            } else if($magic === "\xde\x12\x04\x95"){
                $this->bytecode = "V";
            } else {
                $this->error = true;
                return false;
            }
            
            foreach($this->bytedata AS $key => $value){
                $temp = unpack($this->bytecode, $this->read_bytes(4));
                $temp = array_values($temp);
                $this->bytedata[$key] = $temp[0];
            }
            return true;
        }
        
        /*
         |  CLOSE FILE
         |  @since  1.0.0
         */
        private function close_file(){
            if($this->handle !== NULL){
                fclose($this->handle);
                $this->handle = NULL;
            }
        }
        
        /*
         |  READ BYTES
         |  @since  1.0.0
         */
        private function read_bytes($bytes, $offset = false){
            if($offset === false){
                $offset = $this->cursor;
            }
            fseek($this->handle, $offset);
            
            if($bytes > 8192){ $bytes = 8192; }
            $data = fread($this->handle, $bytes);
            
            if($offset !== false){
                $this->cursor = ftell($this->handle);
            }
            return $data;
        }
        
        /*
         |  START CACHE FILE
         |  @since  1.0.0
         */
        private function cache_file_start(){
            $number = $this->bytedata["number"];
            $original = $this->bytedata["original"];
            $translations = $this->bytedata["translation"];
            
            $this->original = $this->cache_bytes($original, $number);
            $this->translations = $this->cache_bytes($translations, $number);
            
            if($this->config["cache"] === true){
                $this->cache_strings("original", $this->original);
                $this->cache_strings("translations", $this->translations);
            }
        }
        
        /*
         |  CACHE BYTES
         |  @since  1.0.0
         |
         |  @phrase $number * 2     The string count * 2 (Offset + Length)
         |  @phrase $number * 2 *4  The string count * 2 (Offset + Length) * 4 (Number of Bytes = 4)
         */
        private function cache_bytes($offset, $number){
            $bytes = unpack($this->bytecode.($number * 2), $this->read_bytes($number * 2 * 4, $offset));
            $bytes = array_values($bytes);
            
            $first = array(); $second = array();
            for($i = 0; $i < count($bytes); $i++){
                if($i%2 !== 0){
                    $first[] = $bytes[$i];
                } else {
                    $second[] = $bytes[$i];
                }                
            }
            unset($first[0]); unset($second[0]);
            $bytes = array_combine($first, $second);
            return $bytes;
        }
        
        /*
         |  CACHE STRINGS
         |  @since  1.0.0
         */
        private function cache_strings($type, $bytearray){
            foreach($bytearray AS $offset => $length){
                $string = $this->read_bytes($length, $offset);
                
                $context = explode(chr(4), $string);
                if(count($context) === 2){
                    $string = $context[1];
                    $context = $context[0];
                } else {
                    $context = false;
                }
                
                $plural = explode(chr(0), $string);
                if(count($plural) === 2){
                    $string = $plural[0];
                    $plural = $plural[1];
                } else {
                    $plural = false;
                }
                
                $finder = $string;
                if($plural !== false){ $finder .= chr(4).$plural; }
                if($context !== false){ $finder .= chr(4).$context; }
                
                if($type === "original"){
                    $this->original_strings[] = $finder;
                } else {
                    $this->translated_strings[] = $finder;
                }
            }
        }
        
        /*
         |  SEARCH STRING
         |  @since  1.0.0
         */
        private function search_string($string, $plural = NULL, $context = NULL){
            if($this->handle === NULL){
                $this->open_file();
            }
            $check = $this->original; $i = 0;
            foreach($check AS $offset => $length){
                $test = $this->read_bytes($length, $offset);
                
                if($context === NULL && $plural === NULL){
                    if($test === $string){
                        $number = $i;
                        break;
                    }
                } else {
                    $check_plural = explode(chr(0), $test);
                    $check_context = explode(chr(4), $test);
                    
                    if($plural !== NULL && count($check_plural) === 2){
                        if($plural == $check_plural[1]){
                            $plural_okay = true;
                            if($context === NULL){
                                $number = $i;
                                break;
                            }
                        }
                    }
                    
                    if($context !== NULL && count($check_context) === 2){
                        if($context == $check_context[0]){
                            if($plural === NULL || isset($plural_okay)){
                                $number = $i;
                                break;
                            }
                        }
                    }
                }
                $i++;
            }
            
            if(!isset($number)){
                if($this->handle !== NULL){
                    $this->close_file();
                }
                return false;
            }
            
            $offset = array_keys($this->translations);
            $length = array_values($this->translations);
            $offset = $offset[$number]; $length = $length[$number];
            $return = $this->read_bytes($length, $offset);
            if($this->handle !== NULL){
                $this->close_file();
            }
            return $return;
        }
        
        /*
         |  SWITCH LOCALE
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function switch_locale($locale){
            if($this->file !== NULL){
                // CLOSE THE CURRENT FILE (if open)
                if($this->handle !== NULL){
                    $this->close_file();
                }
                
                // RESET STUFF
                $this->file = NULL;
                $this->length = NULL;
                $this->handle = NULL;
                $this->cursor = 0;
                $this->bytecode = "V";
                $this->bytedata = array(
                    "file_format"       => 0, 
                    "number"            => 0, 
                    "original"          => 0, 
                    "translation"       => 0, 
                    "hashing_start"     => 0, 
                    "hashing_offset"    => 0
                );
                $this->original = array();
                $this->translations = array();
                $this->original_strings = array();
                $this->translated_strings = array();
            }
            // SET NEW LOCALE
            $this->locale = $locale;
        }
        
        /*
         |  GET LOCALE
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function get_locale(){
            return array_keys($this->files);
        }
        
        /*
         |  TRANSLATOR
         |  @since  1.1.0
         */
        public function translator($type){
            $args = func_get_args();
            $type = array_shift($args);
            $number = 1;
            
            // GET NUMBER
            if($type == "plural" || $type == "pluralcontext"){
                if(count($args) >= 3){
                    $number = (int) array_pop($args);
                } else {
                    return $args[0];
                }
            }
            
            // CHECKUP
            if($this->error === true || !$this->check_file()){
                if(isset($number) && $number !== 1){
                    return $args[1];
                }
                return $args[0];
            }
            
            // TRANSLATE
            $search = array("string" => "", "plural" => NULL, "context" => NULL, "search" => NULL);
            switch($type){
                case "string":
                    $search["string"] = $args[0];
                    $search["search"] = $args[0];
                    break;
                case "plural":
                    $search["string"] = $args[0];
                    $search["context"] = $args[1];
                    $search["search"] = $args[0].chr(4).$args[1];
                    break;
                case "context":
                    $search["string"] = $args[0];
                    $search["plural"] = $args[1];
                    $search["search"] = $args[0].chr(4).$args[1];
                    break;
                case "pluralcontext":
                    $search["string"] = $args[0];
                    $search["plural"] = $args[1];
                    $search["context"] = $args[2];
                    $search["search"] = $args[0].chr(4).$args[1].chr(4).$args[2];
                    break;
            }
            
            if($search["search"] !== NULL){
                if($this->config["cache"] === true){
                    if(($finder = array_search($search["search"], $this->original_strings)) !== false){
                        $return = explode(chr(4), $this->translated_strings[$finder]);
                    }
                } else {
                    if(($return = $this->search_string($search["string"], $search["plural"], $search["context"])) !== false){
                        $return = explode(chr(0), $return);
                    }
                }
                
                // RETURN TRANSLATION
                if(isset($return)){
                    if(count($return) > 1 && $number !== 1){
                        return $return[1];
                    }
                    return $return[0];
                }
            }
            
            // NO TRANSLATION FOUND OR ON ERROR
            if(isset($number) && $number !== 1){
                return $args[1];
            }
            return $args[0];
        }
    }
