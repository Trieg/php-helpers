<?php
/*
 |  Steam       A basic implementation
 |  @file       ./PIT/Steam/Models/App.php
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
    
    class App{
        /*
         |  INSTANCE DATA
         */
        public $id;
        public $appid;
        
        public $type;
        public $slug;
        public $name;
        public $title;
        public $about;
        public $description;
        public $website;
        
        public $controller;
        public $languages;
        public $platforms;
        public $requirements;
        
        public $genres;
        public $categories;
        public $developers;
        public $publishers;
        
        public $parent_id;
        public $parent_appid;
        public $parent_title;
        
        public $steamurl;
        public $metacritic;
        public $release;
        public $release_date;
        
        /*
         |  CONSTRUCTOR
         |  @since  0.1.0
         */
        public function __construct($app){
            $this->id             = $app->steam_appid;
            $this->appid          = $app->steam_appid;
            
            $this->type           = isset($app->type)? $app->type: NULL;
            $this->slug           = $this->slug(isset($app->name)? $app->name: NULL);
            $this->name           = isset($app->name)? $app->name: NULL;
            $this->title          = isset($app->name)? $app->name: NULL;
            $this->about          = isset($app->about_the_game)? $app->about_the_game: NULL;
            $this->description    = isset($app->detailed_description)? $app->detailed_description: NULL;
            $this->website        = isset($app->website)? $app->website: NULL;
            
            $this->controller     = isset($app->controller_support)? $app->controller_support: false;
            $this->languages      = $this->languages(isset($app->supported_languages)? $app->supported_languages: array());
            $this->platforms      = array();
            $this->requirements   = $this->requirements($app);
            
            $this->genres         = $this->terms(isset($app->genres)? (array) $app->genres: array());
            $this->categories     = $this->terms(isset($app->categories)? (array) $app->categories: array());
            $this->developers     = isset($app->developers)? $app->developers: array();
            $this->publishers     = isset($app->publishers)? $app->publishers: array();
            
            $this->parent_id      = isset($app->fullgame)? $app->fullgame->appid: NULL;
            $this->parent_appid   = isset($app->fullgame)? $app->fullgame->appid: NULL;
            $this->parent_title   = isset($app->fullgame)? $app->fullgame->name: NULL;
            
            $this->steamurl       = "http://store.steampowered.com/app/{$this->id}";
            $this->metacritic     = isset($app->metacritic)? (array) $app->metacritic: array("url" => NULL, "score" => NULL);
            $this->release        = $this->release(isset($app->release_date)? $app->release_date: array());
        }
        
        /*
         |  FORMAT :: SLUG
         |  @since  0.1.0
         */
        private function slug($slug){
            if(!is_string($slug)){
                return NULL;
            }
            $slug = str_replace("--", "-", str_replace(" ", "-", trim($slug)));
            $slug = preg_replace("#[^A-Za-z0-9\-\_]#", "", $slug);
            return trim(strtolower($slug));
        }
        
        /*
         |  FORMAT :: TERMS
         |  @since  0.1.0
         */
        private function terms($terms){
            if(!is_array($terms)){
                return array();
            }
            
            foreach($terms AS &$term){
                if(!isset($term->description) || empty($term->description)){
                    continue;
                }
                $term = $term->description;
            }
            return $terms;
        }
        
        /*
         |  FORMAT :: REQUIREMENTS
         |  @since  0.1.0
         */
        private function requirements($app){
            $return = array();
            if(isset($app->pc_requirements) && !empty($app->pc_requirements)){
                $return["windows"] = $app->pc_requirements;
                $this->platforms[] = "windows";
            }
            if(isset($app->mac_requirements) && !empty($app->mac_requirements)){
                $return["max"] = $app->mac_requirements;
                $this->platforms[] = "mac";
            }
            if(isset($app->linux_requirements) && !empty($app->linux_requirements)){
                $return["linux"] = $app->linux_requirements;
                $this->platforms[] = "linux";
            }
            return $return;
        }
        
        /*
         |  FORMAT :: RELEASE DATE
         |  @since  0.1.0
         */
        private function release($release){
            if(!is_object($release) || !property_exists($release, "date")){
                return NULL;
            }
            $timestamp = strtotime(str_replace(",", "", $release->date));
            $this->release_date = date("Y-m-d", $timestamp);
            return $timestamp;
        }
        
        /*
         |  FORMAT :: LANGUAGES
         |  @since  0.1.0
         */
        private function languages($languages){
            if(!is_string($languages)){
                return array();
            }
            $languages = strip_tags(str_replace("<strong>*</strong>", "", $languages));
            if(!is_array($languages)){
                $languages = array_map("trim", explode(",",  $languages));
            }
            
            $return = array();
            foreach($languages AS $language){
                if(($locale = array_search($language, $this->language_list)) !== false){
                    $return[$locale] = $language;
                }
            }
            return $return;
        }
        private $language_list = array(
            'ab' => 'Abkhazian',
            'aa' => 'Afar',
            'af' => 'Afrikaans',
            'ak' => 'Akan',
            'sq' => 'Albanian',
            'am' => 'Amharic',
            'ar' => 'Arabic',
            'an' => 'Aragonese',
            'hy' => 'Armenian',
            'as' => 'Assamese',
            'av' => 'Avaric',
            'ae' => 'Avestan',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'bm' => 'Bambara',
            'ba' => 'Bashkir',
            'eu' => 'Basque',
            'be' => 'Belarusian',
            'bn' => 'Bengali',
            'bi' => 'Bislama',
            'bs' => 'Bosnian',
            'br' => 'Breton',
            'bg' => 'Bulgarian',
            'my' => 'Burmese',
            'ca' => 'Catalan',
            'ch' => 'Chamorro',
            'ce' => 'Chechen',
            'zh' => 'Chinese',
            'cu' => 'Church Slavic',
            'cv' => 'Chuvash',
            'kw' => 'Cornish',
            'co' => 'Corsican',
            'cr' => 'Cree',
            'hr' => 'Croatian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'dv' => 'Divehi',
            'nl' => 'Dutch',
            'dz' => 'Dzongkha',
            'en' => 'English',
            'eo' => 'Esperanto',
            'et' => 'Estonian',
            'ee' => 'Ewe',
            'fo' => 'Faroese',
            'fj' => 'Fijian',
            'fi' => 'Finnish',
            'fr' => 'French',
            'ff' => 'Fulah',
            'gl' => 'Galician',
            'lg' => 'Ganda',
            'ka' => 'Georgian',
            'de' => 'German',
            'el' => 'Greek',
            'gn' => 'Guarani',
            'gu' => 'Gujarati',
            'ht' => 'Haitian',
            'ha' => 'Hausa',
            'he' => 'Hebrew',
            'hz' => 'Herero',
            'hi' => 'Hindi',
            'ho' => 'Hiri Motu',
            'hu' => 'Hungarian',
            'is' => 'Icelandic',
            'io' => 'Ido',
            'ig' => 'Igbo',
            'id' => 'Indonesian',
            'ia' => 'Interlingua',
            'ie' => 'Interlingue',
            'iu' => 'Inuktitut',
            'ik' => 'Inupiaq',
            'ga' => 'Irish',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'jv' => 'Javanese',
            'kl' => 'Kalaallisut',
            'kn' => 'Kannada',
            'kr' => 'Kanuri',
            'ks' => 'Kashmiri',
            'kk' => 'Kazakh',
            'km' => 'Khmer',
            'ki' => 'Kikuyu',
            'rw' => 'Kinyarwanda',
            'kv' => 'Komi',
            'kg' => 'Kongo',
            'ko' => 'Korean',
            'kj' => 'Kuanyama',
            'ku' => 'Kurdish',
            'ky' => 'Kyrgyz',
            'lo' => 'Lao',
            'la' => 'Latin',
            'lv' => 'Latvian',
            'li' => 'Limburgish',
            'ln' => 'Lingala',
            'lt' => 'Lithuanian',
            'lu' => 'Luba-Katanga',
            'lb' => 'Luxembourgish',
            'mk' => 'Macedonian',
            'mg' => 'Malagasy',
            'ms' => 'Malay',
            'ml' => 'Malayalam',
            'mt' => 'Maltese',
            'gv' => 'Manx',
            'mi' => 'Maori',
            'mr' => 'Marathi',
            'mh' => 'Marshallese',
            'mn' => 'Mongolian',
            'na' => 'Nauru',
            'nv' => 'Navajo',
            'ng' => 'Ndonga',
            'ne' => 'Nepali',
            'nd' => 'North Ndebele',
            'se' => 'Northern Sami',
            'no' => 'Norwegian',
            'nb' => 'Norwegian Bokmål',
            'nn' => 'Norwegian Nynorsk',
            'ny' => 'Nyanja',
            'oc' => 'Occitan',
            'oj' => 'Ojibwa',
            'or' => 'Oriya',
            'om' => 'Oromo',
            'os' => 'Ossetic',
            'pi' => 'Pali',
            'ps' => 'Pashto',
            'fa' => 'Persian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'pa' => 'Punjabi',
            'qu' => 'Quechua',
            'ro' => 'Romanian',
            'rm' => 'Romansh',
            'rn' => 'Rundi',
            'ru' => 'Russian',
            'sm' => 'Samoan',
            'sg' => 'Sango',
            'sa' => 'Sanskrit',
            'sc' => 'Sardinian',
            'gd' => 'Scottish Gaelic',
            'sr' => 'Serbian',
            'sh' => 'Serbo-Croatian',
            'sn' => 'Shona',
            'ii' => 'Sichuan Yi',
            'sd' => 'Sindhi',
            'si' => 'Sinhala',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'so' => 'Somali',
            'nr' => 'South Ndebele',
            'st' => 'Southern Sotho',
            'es' => 'Spanish',
            'su' => 'Sundanese',
            'sw' => 'Swahili',
            'ss' => 'Swati',
            'sv' => 'Swedish',
            'tl' => 'Tagalog',
            'ty' => 'Tahitian',
            'tg' => 'Tajik',
            'ta' => 'Tamil',
            'tt' => 'Tatar',
            'te' => 'Telugu',
            'th' => 'Thai',
            'bo' => 'Tibetan',
            'ti' => 'Tigrinya',
            'to' => 'Tongan',
            'ts' => 'Tsonga',
            'tn' => 'Tswana',
            'tr' => 'Turkish',
            'tk' => 'Turkmen',
            'tw' => 'Twi',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'ug' => 'Uyghur',
            'uz' => 'Uzbek',
            've' => 'Venda',
            'vi' => 'Vietnamese',
            'vo' => 'Volapük',
            'wa' => 'Walloon',
            'cy' => 'Welsh',
            'fy' => 'Western Frisian',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yi' => 'Yiddish',
            'yo' => 'Yoruba',
            'za' => 'Zhuang',
            'zu' => 'Zulu'
        );
    }
