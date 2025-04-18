<?php

// This file must not be accessible through internet!
// For example place this under your home root e.g. /home/spotifybaton/defines.php

foreach ([
    "SPOTIFY_CLIENT_ID" => "", // Dashboard > App > Client ID
    "SPOTIFY_CLIENT_SECRET" => "", // Dashboard > App > Client secret
    "SPOTIFY_STATE" => md5("harri sano, että ei!"), // This provides protection against attacks such as cross-site request forgery.
    "SPOTIFY_REDIRECT_URI" => "https://yourserver.com/", // The URI to redirect to after the user grants or denies permission.
    "SPOTIFY_CACHE" => "/home/spotifybaton/spotify.json", // Contains tokens and other sensitive data, this must not be accessible through internet!
    "SLACK_BOT_TOKEN" => "", // Salck App > OAuth & Permissions > OAuth Tokens > Bot User OAuth Token
    "SLACK_SESSION" => "/home/spotifybaton/slack.json", // Storage for voting data, user/channel cache (because rate limitations) etc...
    "SPOTIFYBATON_LOG" => "/home/spotifybaton/log/main", // File to log some stuff.
    "SPOTIFYBATON_LOG_LEVEL" => "ICNWF", // Level types to write into log file. See method description for detailed info.
    "SPOTIFYBATON_LOG_DT" => "H.m.s", // Log date and/or time format.
    "SPOTIFYBATON_VOTESKIP_LIMIT" => 5, // Set vote skip limit (from 1 to 10).
    "SPOTIFYBATON_VOTESKIP_EXPIRES" => 90000, // Set vote skip expiration time in milliseconds (0 to disable).
    "SPOTIFYBATON_QUEUE_MAX_DURATION" => 522000 // Limit the maximum duration of queued track in milliseconds (0 to disable).
] as $key => $value) {

    define($key, $value);

}