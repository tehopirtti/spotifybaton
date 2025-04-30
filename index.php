<?php

if (is_readable("defines.php")) require_once "defines.php";
elseif (is_readable("../defines.php")) require_once "../defines.php";
require_once "inc/class/spotifybaton.php";

$sb = new SpotifyBaton();

$view = $_COOKIE["view"] ?? "default";
$home = __DIR__;

if (is_readable("view/{$view}/index.php")) {
    require_once "view/{$view}/index.php";
} else {
    $view = "default";
    if (is_readable("view/{$view}/index.php")) {
        require_once "view/{$view}/index.php";
    } else {
        exit("view/{$view}/index.php not readable!");
    }
}

require_once "view/select.php";
