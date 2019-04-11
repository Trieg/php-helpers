<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Models/Game.php
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
    
    class Game{
        /*
         |  INSTANCE DATA
         */
        public $id;
        public $appid;
        
        public $name;
        public $title;
        public $icon;
        public $logo;
        public $header;
        public $community_stats;
        
        public $playtime_two_weeks;
        public $playtime_forever;
        
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($game){
            $this->id               = $app->appid;
            $this->appid            = $app->appid;
            
            $this->name             = isset($app->name)? $app->name: NULL;
            $this->title            = isset($app->name)? $app->name: NULL;
            $this->icon             = isset($this->icon)? $this->imageURL($this->icon): NULL;
            $this->logo             = isset($this->logo)? $this->imageURL($this->logo): NULL;
            $this->header           = $this->imageURL(NULL, true);
            $this->community_stats  = isset($app->has_community_visible_stats)? $app->has_community_visible_stats: 0;
            
            $this->playtime_2weeks  = isset($app->playtime_2weeks)? $app->playtime_2weeks: NULL;
            $this->playtime_forever = isset($app->playtime_forever)? $app->playtime_forever: NULL;
        }
        
        /*
         |  FORMAT :: BUILD IMG URLs
         |  @since  0.1.0
         */
        private function imageURL($hash, $header = false){
            if(!empty($hash)){
                return "://media.steampowered.com/steamcommunity/public/images/apps/{$this->id}/{$hash}.jpg";
            } else if($header){
                return "://cdn.steampowered.com/v/gfx/apps/{$this->id}/header.jpg";
            }
            return NULL;
        }
    }
