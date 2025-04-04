<?php

require_once "../defines.php";
require_once "inc/class/spotifybaton.php";

function format_duration(int $milliseconds): string {

    $seconds = floor($milliseconds / 1000);
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;

    return "{$minutes} min {$seconds} s";

}

$sb = new SpotifyBaton();

?>
<!doctype html>
<html>
    <head>
        <title>SpotifyBaton</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
        <link rel="stylesheet" href="inc/style.css?<?=filemtime("inc/style.css");?>">
    </head>
    <body>
        <fieldset>
            <legend>upcoming</legend>
<?php

foreach ($sb->player_upcoming() as $item) {

    print "<section>";
    print "<img src=\"{$item["cover"]}\">";
    print "<p>{$item["track"]["title"]}</p>";
    print "<p>{$sb->format_artists($item["artists"])}</p>";
    print "<p>" . format_duration($item["duration"]) . "</p>";
    print "<p><small>{$item["track"]["uri"]}</small></p>";
    print "</section>";

}

?>
        </fieldset>
        <fieldset>
            <legend>now playing</legend>
<?php

$item = $sb->player_current();

if (!empty($item["track"]["title"])) {

    print "<section>";
    print "<img src=\"{$item["cover"]}\">";
    print "<p>{$item["track"]["title"]}</p>";
    print "<p>{$sb->format_artists($item["artists"])}</p>";
    print "<p>" . format_duration($item["position"]) . " / " . format_duration($item["duration"]) . "</p>";
    print "<p><small>{$item["track"]["uri"]}</small></p>";
    print "<div class=\"progress\"><div style=\"width: {$item["progress"]}%;\"></div></div>";
    print "</section>";

}

?>
        </fieldset>
        <fieldset>
            <legend>history</legend>
<?php

foreach ($sb->player_history() as $item) {

    print "<section>";
    print "<img src=\"{$item["cover"]}\">";
    print "<p>{$item["track"]["title"]}</p>";
    print "<p>{$sb->format_artists($item["artists"])}</p>";
    print "<p>" . format_duration($item["duration"]) . " <small>" . date("j.n.Y H.i", $item["played"]) . "</small></p>";
    print "<p><small>{$item["track"]["uri"]}</small></p>";
    print "</section>";

}

?>
        </fieldset>
    </body>
</html>
