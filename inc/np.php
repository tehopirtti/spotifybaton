<?php

if (is_readable("../defines.php")) require_once "../defines.php";
elseif (is_readable("../../defines.php")) require_once "../../defines.php";
require_once "../inc/class/spotifybaton.php";

$sb = new SpotifyBaton();

header("Content-Type: application/json; charset=utf-8");

$item = $sb->player_current();

http_response_code(empty($item["track"]["title"]) ? 204 : 200);

print json_encode($item, JSON_PRETTY_PRINT);
