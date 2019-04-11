<?php
/*
 |  Gettext     A small interface for GettextReader
 |  @file       ./PIT/Gettext/Gettext.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    1.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2013 - 2019 SamBrishes, pytesNET <pytes@gmx.net>
 */

    namespace PIT\Gettext;

    use PIT\Gettext\GettextReader as GettextReader;

    class Gettext{
        /*
         |  GENERAL VARs
         */
        public $locale = "en_US";
        public $charset = "UTF-8";
        public $domainer = false;
        
        /*
         |  GETTEXT DOMAINs
         */
        public $domain = "";
        public $domains = array();
        
        
        /*
         |  CONSTRUCTOR
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function __construct($locale = "en_US", $charset = "UTF-8", $domainer = false){
            if(!validate_locale($locale, true)){
                $locale = "en_US";
            }
            if(!in_array($charset, mb_list_encodings())){
                $charset = "UTF-8";
            }
            if(!is_bool($domainer)){
                $domainer = false;
            }
            
            $this->locale = $locale;
            $this->charset = $charset;
            $this->domainer = $domainer;
            $this->construct = true;
        }
        
        /*
         |  BIND GETTEXT DOMAIN
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function bind_domain($domain, $path, $cache = true, $default = false, $charset = ""){
            // CHECK DOMAIN
            if(!preg_match("#^[A-Za-z0-9_-]+$#", $domain) || array_key_exists($domain, $this->domains)){
                return false;
            }
            
            // CHECK PATH
            if(substr($path, -1) !== "/"){
                $path .= "/";
            }
            if(!file_exists($path) || !is_dir($path) || !is_readable($path)){
                return false;
            }
            
            // CHECK CHARSET, CACHE AND DEFAULT
            if(!in_array($charset, mb_list_encodings()) || empty($charset)){
                $charset = $this->charset;
            }
            if(!is_bool($cache)){
                $cache = true;
            }
            if(!is_bool($default)){
                $default = false;
            }
            
            // CREATE DOMAIN ARRAY
            $add = array();
            $add[$domain] = array(
                "path"      => $path,
                "cache"     => $cache,
                "reader"    => NULL,
                "locale"    => $this->locale,
                "charset"   => $charset
            );
            $reader = new GettextReader($domain, $add[$domain], $this->locale, $default);
            if($reader->error === true){
                return false;
            }
            $add[$domain]["reader"] = $reader;
            
            // ADD DOMAIN
            if($default === true){
                if($this->domainer === false || ($this->domainer === true && empty($this->domain))){
                    $this->domain = $domain;
                }
            }
            $this->domains = array_merge($this->domains, $add);
            return true;
        }
        
        /*
         |  UNBIND GETTEXT DOMAIN
         |  @since  1.1.0
         */
        public function unbind_domain($domain){
            if(!array_key_exists($domain, $this->domains)){
                return false;
            }
            
            unset($this->domains[$domain]);
            return true;
        }
        
        /*
         |  CHECK DOMAINS
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function check_domain($data){
            if(is_array($data)){
                $return = array();
                foreach($data AS $domain){
                    if($this->check_domain($domain) !== false){
                        $return[] = $domain;
                    }
                }
                return $return;
            } else if(is_string($data)){
                if(array_key_exists($data, $this->domains)){
                    return $data;
                }
            }
            return false;
        }
        
        /*
         |  SWITCH LOCALE
         |  @since  1.0.0
         */
        public function switch_locale($locale, $domain = ""){
            if(!empty($domain)){
                $domain = $this->check_domain($domain);
                if(empty($domain)){
                    return false;
                }
            }
            
            if(empty($domain)){
                $domain = array_keys($this->domains);
            } elseif(is_string($domain)){
                $domain = array($domain);
            }
            
            foreach($domain AS $data){
                $this->domains[$data]["locale"] = $locale;
                $this->domains[$data]["reader"]->switch_locale($locale);
            }
            return true;
        }
        
        /*
         |  GET LOCALE
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function get_locale($domain = ""){
            if(empty($domain)){ 
                $domain = $this->domain; 
            }
            if(!array_key_exists($domain, $this->domains)){
                return array($this->locale);
            }
            
            $domain = $this->domains[$domain];
            $locale = $domain["reader"]->get_locale();
            return array_merge(array($this->locale), (array) $locale);
        }
        
        /*
         |  GET CURRENT LOCALE
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function get_current_locale($domain = ""){
            if(empty($domain)){ 
                $domain = $this->domain; 
            }
            if(!array_key_exists($domain, $this->domains)){
                return array($this->locale);
            }
            
            $domain = $this->domains[$domain];
            return $domain["locale"];
        }
        
        /*
         |  TRANSLATE - STRING
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function translate__($string, $domain = ""){
            if(!is_string($string)){
                return $string;
            }
            
            if(!empty($domain)){
                $domain = $this->check_domain($domain);
            }
            if(empty($domain)){
                if(empty($this->domain)){
                    return $string;
                }
                $domain = $this->domain;
            }
            
            $domain = $this->domains[$domain];
            return $domain["reader"]->translator("string", $string);
        }
        
        /*
         |  TRANSLATE - PLURAL
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function translate_n($singular, $plural, $number, $domain = ""){
            if(!is_string($singular) || !is_string($plural) || !is_numeric($number)){
                if(!is_numeric($number) || (int) $number == 1){
                    return $singular;
                }
                return $plural;
            }
            $number = (int) $number;
            
            if(!empty($domain)){
                $domain = $this->check_domain($domain);
            }
            if(empty($domain)){
                if(empty($this->domain)){
                    if($number == 1){
                        return $singular;
                    }
                    return $plural;
                }
                $domain = $this->domain;
            }
            
            
            $domain = $this->domains[$domain];
            return $domain["reader"]->translator("plural", $singular, $plural, $number);
        }
        
        /*
         |  TRANSLATE - CONTEXT
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function translate_p($string, $context, $domain = ""){
            if(!is_string($string) || !is_string($context)){
                return $string;
            }
            
            if(!empty($domain)){
                $domain = $this->check_domain($domain);
            }
            if(empty($domain)){
                if(empty($this->domain)){
                    return $string;
                }
                $domain = $this->domain;
            }
            
            $domain = $this->domains[$domain];
            return $domain["reader"]->translator("context", $string, $context);
        }
        
        /*
         |  TRANSLATE - PLURAL CONTEXT
         |  @since  1.0.0
         |  @update 1.1.0
         */
        public function translate_np($singular, $plural, $context, $number, $domain = ""){
            if(!is_string($singular) || !is_string($plural) || !is_string($context) || !is_numeric($number)){
                if(!is_numeric($number) || (int) $number == 1){
                    return $singular;
                }
                return $plural;
            }
            $number = (int) $number;
            
            if(!empty($domain)){
                $domain = $this->check_domain($domain);
            }
            if(empty($domain)){
                if(empty($this->domain)){
                    if($number == 1){
                        return $singular;
                    }
                    return $plural;
                }
                $domain = $this->domain;
            }
            
            $domain = $this->domains[$domain];
            return $domain["reader"]->translator("pluralcontext", $singular, $plural, $context, $number);
        }
    }
    
    /*
     |  VALIDATE LOCALE
     |  @since  1.0.0
     |
     |  @param  string  The locale code.
     |  @param  bool    Strict Match, check also case-sensitive.
     |
     |  @return bool    True if everthing is fluffy, False if not!
     */
    function validate_locale($locale, $format = true){
        if($format === true){
            $regex = "^[a-z]{2,3}\_[A-Z]{2}$";
        } else {
            $regex = "^[a-zA-Z]{2,3}\_[a-zA-Z]{2}$";
        }
    
        if(preg_match("#".$regex."#", $locale)){ 
            return true; 
        }
        return false;
    }
    
    /*
     |  INIT GETTEXT SYSTEM
     |  @since  1.1.0
     |
     |  @param  string  The default locale code.
     |  @param  string  The default charset.
     |  @param  bool    True to allow to switch the default domain.
     |                  False to set only the first default domain as default.
     |
     |  @return multi   False on error, The class instance if success.
     */
    function init_system($locale = "en_US", $charset = "UTF-8", $domainer = false){
        global $Gettext;
        
        if(!validate_locale($locale, true)){
            return false;
        }
        if(!in_array($charset, mb_list_encodings())){
            return false;
        }
        
        $Gettext = new Gettext($locale, $charset, $domainer);
        return $Gettext;
    }
    
    /*
     |  BIND A GETTEXT TEXTDOMAIN
     |  @since  1.1.0
     |
     |  @param  string  The unique gettext domain.
     |  @param  string  The full directory path to the .mo files.
     |  @param  bool    True to cache the full translations, False to cache only the bytes.
     |  @param  bool    True to mark this domain as default, Falso to do it not.
     |  @param  string  The charset for this domain.
     |
     |  @return bool    True if everything is fluffy, False if not!
     */
    function bind_textdomain($domain, $path, $cache = true, $default = false, $charset = ""){
        global $Gettext;
        return $Gettext->bind_domain($domain, $path, $cache, $default, $charset);
    }
    
    /*
     |  UNBIND A GETTEXT TEXTDOMAIN
     |  @since  1.1.0
     |
     |  @param  string  The unique gettext domain.
     |
     |  @return bool    True if everything is fluffy, False if not!
     */
    function unbind_textdomain($domain){
        global $Gettext;
        return $Gettext->unbind_domain($domain);
    }
    
    /*
     |  GET LOCALE LIST
     |  @since  1.0.0
     |
     |  @param  string  The respective gettext domain. (Or an empty string for the default domain.)
     |
     |  @return array   An array with all available languages (+ default language)
     |          bool    False on failure.
     */
    function list_locale($domain = ""){
        global $Gettext;
        return $Gettext->get_locale($domain);
    }
    
    /*
     |  GET CURRENT LOCALE
     |  @since  1.0.0
     |
     |  @param  string  The respective gettext domain. (Or an empty string for the default domain.)
     |
     |  @return string  The current locale code.
     */
    function current_locale($domain = ""){
        global $Gettext;
        return $Gettext->get_current_locale($domain);
    }
    
    /* 
     |  SWITCH LANGUAGE
     |  @since  1.0.0
     |  @update 1.1.0
     |
     |  @param  string  The new locale code.
     |  @param  string  Change the locale only on the respective domain.
     |          array   Change the locale only on this domains.
     |          empty   Change the locale on each domain.
     |    
     | @return  bool    True if everything is fluffy, False if not!
     */
    function switch_locale($locale, $domain = ""){
        global $Gettext;
        if(!validate_locale($locale, true)){
            return false;
        }
        return $Gettext->switch_locale($locale, $domain);
    }
    
    /*
     |  TRANSLATE A STRING
     |  @since  1.0.0
     |
     |  @param  string  The message, which you want to translate.
     |  @param  string  The respective gettext domain. (Or an empty string for the default domain.)
     |
     |  @return string  The translated message or the original message, if not available.
     */
    function gettext($string, $domain = ""){
        global $Gettext;
        return $Gettext->translate__($string, $domain);
    }
    function __($string, $domain = ""){
        return gettext($string, $domain);
    }
    function _e($string, $domain = ""){
        echo gettext($string, $domain);
    }
    
    /*
     |  TRANSLATE A STRING - PLURALVERSION
     |  @since  1.0.0
     |
     |  @param  string  The singluar message, which you want to translate.
     |  @param  string  The plural message, which you want to translate.
     |  @param  int     The respective number.
     |  @param  string  The respective gettext domain. (Or an empty string for the default domain.)
     |
     |  @return string  The translated message or the original message, if not available.
     */
    function ngettext($singular, $plural, $number, $domain = ""){
        global $Gettext;
        return $Gettext->translate_n($singular, $plural, $number, $domain);
    }
    function _n($singular, $plural, $number, $domain = ""){
        return ngettext($singular, $plural, $number, $domain);
    }
    function _en($singular, $plural, $number, $domain = ""){
        echo ngettext($singular, $plural, $number, $domain);
    }
    
    /*
     |  TRANSLATE A STRING - WITH CONTEXT
     |  @since  1.0.0
     |
     |  @param  string  The message, which you want to translate.
     |  @param  string  The context, which should help to translate.
     |  @param  string  The respective gettext domain. (Or an empty string for the default domain.)
     |
     |  @return string  The translated message or the original message, if not available.
     */
    function pgettext($string, $context, $domain = ""){
        global $Gettext;
        return $Gettext->translate_p($string, $context, $domain);
    }
    function _p($string, $context, $domain = ""){
        return pgettext($string, $context, $domain);
    }
    function _ep($string, $context, $domain = ""){
        echo pgettext($string, $context, $domain);
    }
    
    /*
     |  TRANSLATE A STRING - PLURAL WITH CONTEXT
     |  @since  1.0.0
     |
     |  @param  string  The singluar message, which you want to translate.
     |  @param  string  The plural message, which you want to translate.
     |  @param  string  The context, which should help to translate.
     |  @param  int     The respective number.
     |  @param  string  The respective gettext domain. (Or an empty string for the default domain.)
     |
     |  @return string  The translated message or the original message, if not available.
     */
    function npgettext($singular, $plural, $context, $number, $domain = ""){
        global $Gettext;
        return $Gettext->translate_np($singular, $plural, $context, $number, $domain);
    }
    function _np($singular, $plural, $context, $number, $domain = ""){
        return npgettext($singular, $plural, $contex, $number, $domain);
    }
    function _enp($singular, $plural, $context, $number, $domain = ""){
        echo npgettext($singular, $plural, $context, $number, $domain);
    }
