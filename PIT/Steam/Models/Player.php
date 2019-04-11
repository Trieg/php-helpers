<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Models/Player.php
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
    
    class Player{
        /*
         |  INSTANCE DATA
         */
        public $id;
        public $steamid;
        
        public $current_gameid;
        public $current_serverip;
        public $current_extrainfo;
        
        public $avatar;
        public $avatar_medium;
        public $avatar_full;
        public $primary_clan_id;
        
        public $created;
        public $lastlogoff;
        public $visibility;
        
        public $realname;
        public $persona_name;
        public $persona_state;
        public $persona_state_flags;
        public $profile_url;
        public $profile_name;
        public $profile_state;
        
        public $loc_country_code;
        public $loc_state_code;
        public $loc_city_id;
        
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($player){
            $this->id                   = $player->steamid;
            $this->steamid              = $player->steamid;
            
            $this->current_gameid       = isset($player->gameid)? $player->gameid: NULL;
            $this->current_severip      = isset($player->gameserverip)? $player->gameserverip: "0.0.0.0:0";
            $this->current_extrainfo    = isset($player->gameextrainfo)? $player->gameextrainfo: NULL;
            
            $this->avatar               = isset($player->avatar)? $player->avatar: NULL;
            $this->avatar_medium        = isset($player->avatarmedium)? $player->avatarmedium: NULL;
            $this->avatar_full          = isset($player->avatarfull)? $player->avatarfull: NULL;
            $this->primary_clan_id      = isset($player->primaryclanid)? $player->primaryclanid: NULL;
            
            $this->created              = isset($player->timecreated)? $player->timecreated: NULL;
            $this->lastlogoff           = isset($player->lastlogoff)? $player->lastlogoff: NULL;
            $this->visibility           = $this->visibility(isset($player->communityvisibilitystate)? $player->communityvisibilitystate: NULL);
            
            $this->realname             = isset($player->realname)? $player->realname: NULL;
            $this->persona_name         = isset($player->personaname)? $player->personaname: NULL;
            $this->persona_state        = $this->personaState(isset($player->personastate)? $player->personastate: false);
            $this->persona_state_flags  = isset($player->personastateflags)? $player->personastateflags: NULL;
            $this->profile_url          = isset($player->profileurl)? $player->profileurl: NULL;
            $this->profile_name         = $this->profileName(isset($player->profileurl)? $player->profileurl: false);
            $this->profile_state        = isset($player->profilestate)? $player->profilestate: NULL;
            
            $this->loc_country_code     = isset($player->loccountrycode)? $player->loccountrycode: NULL;
            $this->loc_state_code       = isset($player->locstatecode)? $player->locstatecode: NULL;
            $this->loc_city_id          = isset($player->loccityid)? $player->loccityid: NULL;
        }
        
        /*
         |  FORMAT :: PERSONA STATE
         |  @since  0.1.0
         */
        private function personaState($state){
            switch($state){
                case 0:
                    return array($state, "Offline");
                case 1:
                    return array($state, "Online");
                case 2:
                    return array($state, "Busy");
                case 3:
                    return array($state, "Away");
                case 4:
                    return array($state, "Snooze");
                case 5:
                    return array($state, "Looking to Trade");
                case 6:
                    return array($state, "Looking to Play");
            }
            return false;
        }
        
        /*
         |  FORMAT :: PROFILE NAME
         |  @since  0.1.0
         */
        private function profileName($string){
            if(!is_string($string)){
                return false;
            }
            $string = rtrim($string, "/");
            return array_reverse(explode("/", $string))[0];
        }
        
        /*
         |  FORMAT :: VISIBILITY
         |  @since  0.1.0
         */
        private function visibility($code){
            switch($code){
                case 1:
                    return array($code, "Private");
                case 3:
                    return array($code, "Public");
                default:
                    return array(NULL, NULL);
            }
        }
    }
