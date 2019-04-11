<?php

    spl_autoload_register(function($class){
        if(strpos($class, "PIT\\") !== false){
            $path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
            $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
            
            if(file_exists($class . ".php")){
                require_once $class . ".php";
            }
            return true;
        }
        return false;
    });
