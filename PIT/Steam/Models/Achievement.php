<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Models/Achievement.php
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

    class Achievement{
        /*
         |  INSTANCE DATA
         */
        public $apiname;
        public $achieved;
        
        public $name;
        public $title;
        public $description;
        
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($achievement){
            $this->apiname      = isset($achievement->apiname)? $achievement->apiname: NULL;
            $this->achieved     = isset($achievement->achieved)? $achievement->achieved: NULL;
            
            $this->name         = isset($achievement->name)? $achievement->name: NULL;
            $this->title        = isset($achievement->name)? $achievement->name: NULL;
            $this->description  = isset($achievement->description)? $achievement->description: NULL;
        }
    }
