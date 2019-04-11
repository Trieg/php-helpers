<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Models/News.php
 |  @author     SamBrishes <sam@pytes.net>
 |  @version    0.1.0
 |
 |  @website    https://github.com/pytesNET/php-helpers
 |  @license    X11 / MIT License
 |  @copyright  Copyright © 2018 - 2019 SamBrishes, pytesNET <pytes@gmx.net>
 |
 |  @fork       Fork of Steam Web API by Joao Lopes
 |              https://github.com/DPr00f/steam-web-api-php
 */
    
    namespace PIT\Steam\Models;
    
    class News{
        /*
         |  INSTANCE DATA
         */
        public $id;
        public $gid;
        public $appid;
        
        public $title;
        public $content;
        public $excerpt;
        
        public $date;
        public $url;
        public $is_external_url;
        
        public $thumbnail;
        
        public $author;
        public $author_email;
        
        public $feedtype;
        public $feedname;
        public $feedlabel;
         
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($item){
            $this->id               = isset($item->gid)? $item->gid: NULL;
            $this->gid              = isset($item->gid)? $item->gid: NULL;
            $this->appid            = isset($item->appid)? $item->appid: NULL;
            
            $this->title            = isset($item->title)? $item->title: NULL;
            $this->content          = isset($item->contents)? $item->contents: NULL;
            $this->excerpt          = isset($item->contents)? $this->excerpt($item->contents): NULL;
            $this->author           = isset($item->author)? $this->author($item->author): NULL;
            $this->date             = isset($item->date)? $item->date: NULL;
            $this->url              = isset($item->url)? $item->url: NULL;
            $this->is_external_url  = isset($item->is_external_url)? $item->is_external_url: false;
            
            $this->thumbnail        = isset($item->contents)? $this->thumbnail($item->contents): NULL;
            
            $this->feedtype         = isset($item->feed_type)? $item->feed_type: NULL;
            $this->feedname         = isset($item->feedname)? $item->feedname: NULL;
            $this->feedlabel        = isset($item->feedlabel)? $item->feedlabel: NULL;
        }
        
        /*
         |  FORMAT :: AUTHOR
         |  @since  0.1.0
         */
        public function author($author){
            if(empty($author)){
                return NULL;
            }
            if(strpos($author, "@") !== false){
                $parts = explode(" ", $author);
                foreach($parts AS &$part){
                    if(filter_var($part, FILTER_VALIDATE_EMAIL) !== false){
                        $this->author_email = $part;
                        $part = NULL;
                    }
                }
                $author = implode(" ", array_filter($parts));
            }
            return ucwords(preg_replace("#[^a-zA-Z0-9\'\"\-\_ ]#", "", $author));
        }
        
        /*
         |  FORMAT :: THUMBNAIL
         |  @since  0.1.0
         */
        public function thumbnail($content){
            $content = strip_tags($content, "<img><img />");
            if(!preg_match("#<img#", $content)){
                return NULL;
            }
            $thumbnail = NULL;
            
            $dom = new DOMDocument();
            $dom->loadHTML($content);
            $path = new DOMXPath($dom);
            foreach($path->query("//img") AS $image){
                $image_url = $image->getAttribute("src");
                if(empty($image_url)){
                    continue;
                }
                
                if(function_exists("curl_init")){
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $image_url);
                    curl_setopt($curl, CURLOPT_NOBODY, true);       // Prevent Content Download
                    curl_setopt($curl, CURLOPT_FAILONERROR, true);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    if(curl_exec($curl) !== false){
                        $thumbnail = $image_url;
                        break;
                    }
                } else {
                    if(@getimagesize($image_url)){
                        $thumbnail = $image_url;
                        break;
                    }
                }
            }
            return $thumbnail;
        }
        
        /*
         |  FORMAT :: EXCERPT
         |  @since  0.1.0
         */
        public function excerpt($content){
            $content = strip_tags($content, "<a><b><strong><em><i><u><strike><s>");
            if(strlen($content) > 550){
                $content  = wordwrap($content, 550, "<br />");
                $content  = explode("<br />", $content)[0];
                $content .= "...";
            }
            return $content;
        }
    }
