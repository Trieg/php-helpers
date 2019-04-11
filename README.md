PIT PHP Helpers
===============
My Collection of (useful) PHP helper classes, the most of them are coded completely on my own and 
licensed under the MIT license (except the `Neocities` API interface, which is published under 
Public Domin / CC0). The `Gravatar`, `Kses`, `Steam` and `Validate` classes are contributions or 
forks I made, and with the exception of the `Steam` class, all are released under the GNU GPL 
license.

--------

`PIT\eMail` - A basic eMail Handler
-----------------------------------
>   Written in 2018<br>
>   Published under the MIT License

This is just a small eMail handler class, which currently does ONLY support the default PHP own 
`mail` function. I'm trying to add Support for SMTP and SendMail as well as for attachments and 
other functionalities in future releases.

--------

`PIT\Gettext` - A basic `PIT\GettextReader` Interface
-----------------------------------------------------
>   Written in 2013<br>
>   Published under the MIT License

My really first own helper class was "another" Gettext Interface. This helper just extends the 
`GettextReader` class, which offers all required functionallity to read .MO files.

--------

`PIT\GettextReader` - Another PHP Gettext library
-------------------------------------------------
>   Written in 2013<br>
>   Published under the MIT License

My really first own helper class was "another" Gettext Interface. This class gets extended by the 
`Gettext` helper, which offers a basic interface and some other general functionilities.

--------

`PIT\Gravatar` - A basic Gravatar API handler
---------------------------------------------
>   Written in 2008<br>
>   Published under the GNU GPL v3<br>
>   Authors: Philippe Archambault, Martijn van der Kleijn, SamBrishes

This helper class allows you to use the Gravatar API, made by Automaticc (the WordPress developer). 
Originally written for the Frog CMS, taken by the Wolf CMS developers, and a bit extended / updated 
by me for my "Fox CMS" (a fork of the Wolf CMS).

--------

`PIT\Kses` - Custom version of KSES Strips Evil Scripts
-------------------------------------------------------
>   Written in 2002<br>
>   Published under the GNU GPL v2<br>
>   Authors: Ulf Harnhammar, SamBrishes

The Kses system is a famous (X) HTML validator / sanitizer, which has also been forked by WordPress. 
This is my, little improved, version of the really awesome Kses helper system.

--------

`PIT\MySQLi` - A basic MYSQLi wrapper
-------------------------------------
>   Written in 2016<br>
>   Published under the MIT License

A MySQLi wrapper class, which provides an easy to use interface and many useful functions and 
system. Perfect to start an own MySQLi-based (content mangement) system.

--------

`PIT\Neocities` - A basic Neocities API handler
-----------------------------------------------
>   Written in 2018<br>
>   Published under CC0 / Public Domain

The Neocities API interface handles ... well ... the API interface of Neocities.

--------

`PIT\PDO` - A basic PDO wrapper
-------------------------------
>   Written in 2016<br>
>   Published under the MIT License

A PDO wrapper class, which provides an easy to use interface and many useful functions and 
system. Perfect to start an own PDO-based (content mangement) system.

--------

`PIT\Steam` - A basic Steam API handler
---------------------------------------
>   Written in 2018<br>
>   Published under the MIT License

Another small Steam API interface, which is born as fork of the [Steam Web API](https://github.com/DPr00f/steam-web-api-php)
helper by Joao Lopes.

--------

`PIT\Validate` - A basic Validation helper
------------------------------------------
>   Written in 2008<br>
>   Published under the GNU GPL v3<br>
>   Authors: Philippe Archambault, Martijn van der Kleijn, SamBrishes

This helper class allows you to extend your system with some basic validations and sanitizations.
Originally written for the Frog CMS, taken by the Wolf CMS developers, and a bit extended / updated 
by me for my "Fox CMS" (a fork of the Wolf CMS).

--------

`PIT\ZIP` - A basic PKZIP helper / ZipArchive wrapper
-----------------------------------------------------
>   Written in 2015<br>
>   Published under the MIT License

This helper class offers a really basic and vanilla PKZip interface, but also allows to use the 
ZipArchive handle, if available.
