<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Models/PlayerBans.php
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
    
    class PlayerBans{
        /*
         |  INSTANCE DATA
         */
        public $id;
        public $steamid;
        
        public $community_ban;
        public $vac_ban;
        public $vac_ban_number;
        public $days_since_last_ban;
        public $economy_ban;
        
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($player){
            $this->id                   = $player->SteamId;
            $this->steamid              = $player->SteamId;
            
            $this->community_ban        = isset($player->CommunityBanned)? $player->CommunityBanned: false;
            $this->vac_ban              = isset($player->VACBanned)? $player->VACBanned: false;
            $this->vac_ban_number       = isset($player->NumberOfVACBans)? intval($player->NumberOfVACBans): 0;
            $this->days_since_last_ban  = isset($player->DaysSinceLastBan)? intval($player->DaysSinceLastBan): 0;
            $this->economy_ban          = isset($player->EconomyBan)? $player->EconomyBan: false;
        }
    }
