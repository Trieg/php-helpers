<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Steam.php
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

    namespace PIT\Steam;
    
    use PIT\Steam\Model\Achievement as Achievement;
    use PIT\Steam\Model\App as App;
    use PIT\Steam\Model\Game as Game;
    use PIT\Steam\Model\News as News;
    use PIT\Steam\Model\Player as Player;
    use PIT\Steam\Model\PlayerBans as PlayerBans;
    
    class Steam{
        const API = "://api.steampowered.com/";
        const STORE = "://store.steampowered.com/";
        
        /*
         |  GLOBAL VARs
         */
        protected $key = NULL;
        protected $https = false;
        protected $format = "json";
        
        /*
         |  INSTANCE
         */
        protected $interface;
        protected $method;
        protected $version = 2;
        
        public $lastResponse;
        public $lastResponseCode;
        
        
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($key, $format = "json", $https = false){
            if(!is_string($key)){
                trigger_error("You need to pass your Steam Web API key!", E_USER_ERROR);
            } else {
                $this->key = $key;
            }
        }
        
        /*
         |  CORE :: BUILD URL
         |  @since  0.1.0
         */
        protected function buildURL($query){
            $return  = ($this->https)? "https": "http";
            $return .= ($this->interface == "api")? self::STORE: self::API;
            
            if(!empty($this->interface)){
                $return .= "{$this->interface}/";
            }
            if(!empty($this->method)){
                $return .= "{$this->method}/";
            }
            if(!empty($this->version)){
                $return .= "v{$this->version}/";
            }
            return $return . "?" . $query;
        }
        
        /*
         |  CORE :: GET RESPONSE
         |  @since  0.1.0
         */
        protected function getResponse($parameters = array()){
            $parameters = http_build_query(array_merge(array(
                "key"       => $this->key,
                "format"    => $this->format
            ), $parameters));
            
            // CURL
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $this->buildURL($parameters));
            $content = curl_exec($curl);
            $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            // Last Data
            $this->lastResponse     = $content;
            $this->lastResponseCode = $response;
            
            // Response
            if($response != 200){
                return false;
            }
            
            // To JSON
            $content = json_decode($content);
            if(empty($content)){
                return false;
            }
            
            // Check Return
            $check = isset($content->response)? $content->response: $content;
            if(isset($check->success) && !$check->success){
                return false;
            }
            return $content;
        }
        
        /*
         |  CORE :: TO OBJECTs
         |  @since  0.1.0
         */
        protected function toObjects($data, $class, $key = NULL){
            if(empty($data) || (!is_array($data) && !is_object($data))){
                return false;
            }
            if(!class_exists($class)){
                return false;
            }
            
            $return = array();
            foreach($data as $id => $item){
                if(!empty($key) && property_exists($item, $key)){
                    $return[] = new $class($item->$key);
                } else {
                    $return[] = new $class($item);
                }
            }
            return (count($return) == 1)? $return[0]: $return;
        }
        
        /*
         |  STORE :: APPDETAILS
         |  @since  0.1.0
         */
        public function appdetails($appid){
            $this->interface    = "api";
            $this->method       = __FUNCTION__;
            $this->version      = NULL;
            
            $arguments = array("appids" => $appid);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $this->toObjects($response, "App", "data");
        }
        
        /*
         |  APP :: GET APP LIST
         |  @since  0.1.0
         */
        public function GetAppList(){
            $this->interface    = "ISteamApps";
            $this->method       = __FUNCTION__;
            $this->version      = 2;
            
            $arguments = array();
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->applist->apps;
        }
        
        /*
         |  APP :: GET SERVERS AT ADDRESS
         |  @since  0.1.0
         */
        public function GetServersAtAddress($address){
            $this->interface    = "ISteamApps";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("addr" => $address);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response->servers;
        }
        
        /*
         |  APP :: UP TO DATE CHECK
         |  @since  0.1.0
         */
        public function UpToDateCheck($appid, $version){
            $this->interface    = "ISteamApps";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("appid" => $appid, "version" => $version);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response;
        }
        
        /*
         |  USER :: GET PLAYER SUMMARIES
         |  @since  0.1.0
         */
        public function GetPlayerSummaries($steamid){
            $this->interface    = "ISteamUser";
            $this->method       = __FUNCTION__;
            $this->version      = 2;
            
            $arguments = array("steamids" => $steamid);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $this->toObjects($response->response->players, "Player");
        }
        
        /*
         |  USER :: GET FRIEND LIST
         |  @since  0.1.0
         */
        public function GetFriendList($steamid, $relationship = "all"){
            $this->interface    = "ISteamUser";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            if(!in_array($relationship, array("all", "friend"))){
                $relationship = "all";
            }
            
            $arguments = array("steamid" => $steamid, "relationship" => $relationship);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            
            $friends = array();
            $profiles = array();
            foreach($response->friendslist->friends as $friend){
                $friends[$friend->steamid] = array(
                    "profile"       => NULL,
                    "friend_since"  => $friend->friend_since,
                    "relationship"  => $friend->relationship
                );
                $profiles[] = $friend->steamid;
            }
            
            $profiles = $this->GetPlayerSummaries(implode(",", $profiles));
            foreach($profiles AS $profile){
                $friends[$profile->id]["profile"] = $profile;
            }
            return $friends;
        }
        
        /*
         |  USER :: GET PLAYER BANS
         |  @since  0.1.0
         */
        public function GetPlayerBans($steamid){
            $this->interface    = "ISteamUser";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamids" => $steamid);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $this->toObjects($response->players, "PlayerBans");
        }
        
        /*
         |  USER :: GET USER GROUP LIST
         |  @since  0.1.0
         */
        public function GetUserGroupList($steamid){
            $this->interface    = "ISteamUser";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response->groups;
        }
        
        /*
         |  USER :: GET FRIEND LIST
         |  @since  0.1.0
         |
         |  @param  string  The Vanity URL to get a SteamID for (or rather: The last URL
         |                  element of the profile / group URL to get the SteamID).
         |  @param  int     The type of vanity URL. 
         |                  1: Individual profile, 2: Group, 3: Official game group.
         |
         |  @return int     The SteamID or NULL if not found, FALSE on failure.
         */
        public function ResolveVanityUrl($vanity, $type = 1){
            $this->interface    = "ISteamUser";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            if(!in_array($type, array(1, 2, 3))){
                $type = 1;
            }
            
            $arguments = array("vanityurl" => $vanityURL, "url_type" => $type);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return property_exists($response->response, "steamid")? strval($response->response->steamid): NULL;
        }
        
        /*
         |  PLAYER :: GET STEAM LEVEL
         |  @since  0.1.0
         */
        public function GetSteamLevel($steamid){
            $this->interface    = "IPlayerService";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid);
            $arguments = array("input_json" => json_encode($arguments));
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return intval($response->player_level->player_level);
        }
        
        /*
         |  PLAYER :: GET BADGES
         |  @since  0.1.0
         */
        public function GetBadges($steamid){
            $this->interface    = "IPlayerService";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid);
            $arguments = array("input_json" => json_encode($arguments));
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response->badges;
        }
        
        /*
         |  PLAYER :: GET COMMUNITY BADGE PROGRESS
         |  @since  0.1.0
         */
        public function GetCommunityBadgeProgress($steamid, $badgeid = NULL){
            $this->interface    = "IPlayerService";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid);
            if(!is_null($badgeid)){
                $arguments["badgeid"] = $badgeid;
            }
            
            $arguments = array("input_json" => json_encode($arguments));
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response;
        }
        
        /*
         |  PLAYER :: GET OWNED GAMES
         |  @since  0.1.0
         */
        public function GetOwnedGames($steamid, $appinfo = true, $freegames = false, $filter = array()){
            $this->interface    = "IPlayerService";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array(
                "steamid"                       => $steamid,
                "include_appinfo"               => !!$appinfo,
                "include_played_free_games"     => !!$freegames,    
                "appids_filter"                 => is_array($filter)? $filter: array()
            );
            
            $arguments = array("input_json" => json_encode($arguments));
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $this->toObjects($response->response->games, "Game");
        }
        
        /*
         |  PLAYER :: GET RECENTLY PLAYED GAMES
         |  @since  0.1.0
         */
        public function GetRecentlyPlayedGames($steamid, $count = NULL){
            $this->interface    = "IPlayerService";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid);
            if(!is_null($count)){
                $arguments["count"] = !!$count;
            }
            
            $arguments = array("input_json" => json_encode($arguments));
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            if($response->response->total_count > 0){
                return $this->toObjects($response->response->games, "Game");
            }
            return NULL;
        }
        
        /*
         |  PLAYER :: IS PLAYING SHARED GAME
         |  @since  0.1.0
         */
        public function IsPlayingSharedGame($steamid, $appid){
            $this->interface    = "IPlayerService";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid, "appid_playing" => $appid);
            $arguments = array("input_json" => json_encode($arguments));
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response->lender_steamid;
        }
        
        /*
         |  STATS :: GET BLOBAL STATS FOR GAME
         |  @since  0.1.0
         */
        public function GetGlobalStatsForGame($appid, $stats, $language = "english"){
            $this->interface    = "ISteamUserStats";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array(
                "count" => count($stats),
                "appid" => $appid,
                "l"     => $language
            );
            for($i = 0; $i < $count; $i++){
                $arguments["name[{$i}]"] = $stats[$i];
            }
            
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response->globalstats;
        }
        
        /*
         |  STATS :: GET NUMBER OF CURRENT PLAYERs
         |  @since  0.1.0
         */
        public function GetNumberOfCurrentPlayers($appid){
            $this->interface    = "ISteamUserStats";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("appid" => $appid);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->response->player_count;
        }
        
        /*
         |  STATS :: GET SCHEMA FOR GAME
         |  @since  0.1.0
         */
        public function GetSchemaForGame($appid){
            $this->interface    = "ISteamUserStats";
            $this->method       = __FUNCTION__;
            $this->version      = 2;
            
            $arguments = array("appid" => $appid);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->game;
        }
        
        /*
         |  STATS :: GET PLAYER ACHIEVEMENTS
         |  @since  0.1.0
         */
        public function GetPlayerAchievements($steamid, $appid, $language = "english"){
            $this->interface    = "ISteamUserStats";
            $this->method       = __FUNCTION__;
            $this->version      = 1;
            
            $arguments = array("steamid" => $steamid, "appid" => $appid, "l" => $language);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $this->toObjects($response->playerstats->achievements, "Achievement");
        }
        
        /*
         |  STATS :: GET GLOBAL ACHIEVEMENT PERVENTAGES FOR APP
         |  @since  0.1.0
         */
        public function GetGlobalAchievementPercentagesForApp($gameid, $language = "english"){
            $this->interface    = "ISteamUserStats";
            $this->method       = __FUNCTION__;
            $this->version      = 2;
            
            $arguments = array("gameid" => $gameid, "l" => $language);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->achievementpercentages;
        }
        
        /*
         |  STATS :: GET USER STATS FOR GAME
         |  @since  0.1.0
         */
        public function GetUserStatsForGame($steamid, $appid, $language = "english"){
            $this->interface    = "ISteamUserStats";
            $this->method       = __FUNCTION__;
            $this->version      = 2;
            
            $arguments = array("steamid" => $steamid, "appid" => $appid, "l" => $language);
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $response->playerstats;
        }
        
        /*
         |  NEWS :: GET NEWS FOR APP
         |  @since  0.1.0
         */
        public function GetNewsForApp($appid, $count = 5, $length = NULL, $enddate = NULL){
            $this->interface    = "ISteamNews";
            $this->method       = __FUNCTION__;
            $this->version      = 2;
            
            $arguments = array("appid" => $appid, "count" => $count);
            if(!is_null($length)){
                $arguments["maxlength"] = $length;
            }
            if(!is_null($enddate)){
                $arguments["enddate"] = $enddate;
            }
            if(($response = $this->getResponse($arguments)) == false){
                return false;
            }
            return $this->toObjects($response->appnews->newsitems, "News");
        }
    }
