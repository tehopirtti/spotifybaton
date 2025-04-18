<?php

class SpotifyBaton {

    protected false|CurlHandle $curl;
    private string $access_token = "", $refresh_token = "";
    private int $expires = 0;
    private float $runtime;

    function __construct() {

        $this->runtime = microtime(true);

        if (empty($this->curl)) {

            $this->curl = curl_init();

        }

        if (!empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "GET" && !empty($_SERVER["QUERY_STRING"])) {

            parse_str($_SERVER["QUERY_STRING"], $query);

            if (!empty($query["code"]) && !empty($query["state"]) && $query["state"] == SPOTIFY_STATE) {

                $this->log("User returned from authorization with code", "I");

                // User returned from authorization with code, let's exchange it into some tokens!

                $this->request_access_token($query["code"]);

            }

            // Get rid of yucky GET parameters

            header("Location: ./");

            return true;

        }

        if (file_exists(SPOTIFY_CACHE)) {

            $cache = json_decode(file_get_contents(SPOTIFY_CACHE), true);

            if (!empty($cache["access_token"])) {

                $this->handle_token($cache, false);

            }

        }

        if (empty($this->access_token)) {

            $this->log("Access token missing!", "W");

            // No token! Forward user into authorization to get em.

            header("Location: https://accounts.spotify.com/authorize?" . http_build_query([
                    "response_type" => "code",
                    "client_id" => SPOTIFY_CLIENT_ID,
                    "scope" => implode(" ", [
                        "user-read-playback-state",
                        "user-read-currently-playing",
                        "user-read-recently-played",
                        "user-modify-playback-state",
                        "playlist-read-private",
                        "playlist-read-collaborative"
                    ]),
                    "redirect_uri" => SPOTIFY_REDIRECT_URI,
                    "state" => SPOTIFY_STATE,
                    "show_dialog" => "false"
                ]));

            return true;

        }

        if ($this->expires < time()) {

            $this->log("Access token expired!", "N");

            $this->refresh_access_token($this->refresh_token);

        }

        return true;

    }

    function __destruct() {

        if (!empty($this->curl)) {

            curl_close($this->curl);

        }

        $this->log(str_pad("[ " . (microtime(true) - $this->runtime) . " ]", 40, "-", STR_PAD_BOTH), "I");

    }

    /**
     * Request an access token
     *
     * If the user accepted your request, then your app is ready to exchange the authorization code for an access token.
     * It can do this by sending a POST request to the /api/token endpoint.
     *
     * @param string $code
     *
     * @return bool
     */
    private function request_access_token(string $code): bool {

        $this->log("Requesting access token...", "I");

        $this->curl_options([
            "url" => "https://accounts.spotify.com/api/token",
            "authorization" => "basic",
            "postfields" => [
                "grant_type" => "authorization_code",
                "code" => $code,
                "redirect_uri" => SPOTIFY_REDIRECT_URI
            ]
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        $this->handle_token($response);

        return true;

    }

    /**
     * Refreshing tokens
     *
     * A refresh token is a security credential that allows client applications to obtain new access tokens without requiring users to reauthorize the application.
     * Access tokens are intentionally configured to have a limited lifespan (1 hour), at the end of which, new tokens can be obtained by providing the original refresh token acquired during the authorization token request response.
     *
     * @param string $refresh_token
     *
     * @return bool
     */
    private function refresh_access_token(string $refresh_token): bool {

        $this->log("Refreshing access token...", "I");

        $this->curl_options([
            "url" => "https://accounts.spotify.com/api/token",
            "authorization" => "basic",
            "postfields" => [
                "grant_type" => "refresh_token",
                "refresh_token" => $refresh_token
            ]
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        $this->handle_token($response);

        return true;

    }

    /**
     * Handle retrieved tokens and store them for later use.
     *
     * @param array $data
     * @param bool $save_cache
     *
     * @return bool
     */
    private function handle_token(array $data, bool $save_cache = true): bool {

        $this->log("Handling token...", "I");

        foreach ([
            "access_token",
            "token_type",
            "expires_in"
        ] as $key) {

            if (!isset($data[$key])) return false;

        }

        if (!isset($data["expires"])) {

            $data["expires"] = time() + $data["expires_in"];

        }

        if (empty($data["refresh_token"])) {

            $this->log("No new refresh token", "N");

            // When a refresh token is not returned, continue using the existing token.

            $data["refresh_token"] = $this->refresh_token;

        } else {

            $this->log("New refresh token", "N");

        }

        $this->access_token = strval($data["access_token"]);
        $this->refresh_token = strval($data["refresh_token"]);
        $this->expires = intval($data["expires"]);

        if ($save_cache) {

            file_put_contents(SPOTIFY_CACHE, json_encode($data), JSON_PRETTY_PRINT);

            $this->log("Token data cached", "I");

        }

        return true;

    }

    /**
     * Get Recently Played Tracks
     *
     * Get tracks from the current user's recently played tracks. Note: Currently doesn't support podcast episodes.
     *
     * @param $limit int
     * @param $reverse bool
     *
     * @return array
     */
    public function player_history(int $limit = 3, bool $reverse = false): array {

        $this->log("Recently played tracks", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/recently-played?limit={$limit}",
            "authorization" => "bearer"
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        if (!$this->curl_response_class(2)) return [];

        $tracks = [];

        foreach ($response["items"] as $i => $track) {

            $tracks[] = $this->format_track($track);

        }

        if ($reverse) {

            krsort($tracks);

        }

        return $tracks;

    }

    /**
     * Get Currently Playing Track
     *
     * Get the object currently being played on the user's Spotify account.
     *
     * @return array
     */
    public function player_current(): array {

        $this->log("Currently playing track", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/currently-playing",
            "authorization" => "bearer"
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        if (!$this->curl_response_class(2)) return [];

        return empty($response) ? [] : $this->format_track($response);

    }

    /**
     * Get the User's Queue
     *
     * Get the list of objects that make up the user's queue.
     *
     * @param int $limit
     * @param bool $reverse
     *
     * @return array
     */
    public function player_upcoming(int $limit = 3, bool $reverse = true): array {

        $this->log("User's queue", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/queue",
            "authorization" => "bearer"
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        if (!$this->curl_response_class(2)) return [];

        $tracks = [];

        foreach ($response["queue"] as $i => $track) {

            if ($i >= $limit) break;

            $tracks[] = $this->format_track($track);

        }

        if ($reverse) {

            krsort($tracks);

        }

        return $tracks;

    }

    /**
     * Add Item to Playback Queue
     *
     * Add an item to the end of the user's current playback queue.
     * This API only works for users who have Spotify Premium.
     * The order of execution is not guaranteed when you use this API with other Player API endpoints.
     *
     * @param string $uri
     *
     * @return bool
     */
    public function player_queue(string $uri): bool {

        if (empty($uri)) return false;

        $this->log("Add track to queue", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/queue?uri=" . urlencode($uri),
            "authorization" => "bearer",
            "post" => true
        ]);

        curl_exec($this->curl);

        return $this->curl_response_class(2);

    }

    /**
     * Start/Resume Playback
     *
     * Start a new context or resume current playback on the user's active device.
     * This API only works for users who have Spotify Premium.
     * The order of execution is not guaranteed when you use this API with other Player API endpoints.
     *
     * @return bool
     */
    public function player_play(): bool {

        $this->log("Start playback", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/play",
            "authorization" => "bearer",
            "customrequest" => "PUT"
        ]);

        curl_exec($this->curl);

        return $this->curl_response_class(2);

    }

    /**
     * Pause Playback
     *
     * Pause playback on the user's account.
     * This API only works for users who have Spotify Premium.
     * The order of execution is not guaranteed when you use this API with other Player API endpoints.
     *
     * @return bool
     */
    public function player_pause(): bool {

        $this->log("Pause playback", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/pause",
            "authorization" => "bearer",
            "customrequest" => "PUT"
        ]);

        curl_exec($this->curl);

        return $this->curl_response_class(2);

    }

    /**
     * Skip To Next
     *
     * Skips to next track in the user’s queue.
     * This API only works for users who have Spotify Premium.
     * The order of execution is not guaranteed when you use this API with other Player API endpoints.
     *
     * @return bool
     */
    public function player_next(): bool {

        $this->log("Next track", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/next",
            "authorization" => "bearer",
            "post" => true
        ]);

        curl_exec($this->curl);

        return $this->curl_response_class(2);

    }

    /**
     * Skip To Previous
     *
     * Skips to previous track in the user’s queue.
     * This API only works for users who have Spotify Premium.
     * The order of execution is not guaranteed when you use this API with other Player API endpoints.
     *
     * @return bool
     */
    public function player_previous(): bool {

        $this->log("Previous track", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/me/player/previous",
            "authorization" => "bearer",
            "post" => true
        ]);

        curl_exec($this->curl);

        return $this->curl_response_class(2);

    }

    /**
     * Search for Item
     *
     * Get Spotify catalog information about albums, artists, playlists, tracks, shows, episodes or audiobooks that match a keyword string.
     * Audiobooks are only available within the US, UK, Canada, Ireland, New Zealand and Australia markets.
     *
     * @param string $query
     * @param int $limit
     * @param string $type
     *
     * @return array
     */
    public function search(string $query, int $limit = 3, string $type = "track"): array {

        $this->log("Search items", "C");

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/search?limit={$limit}&type={$type}&q=" . urlencode($query),
            "authorization" => "bearer"
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        if (!$this->curl_response_class(2)) return [];

        $tracks = [];

        foreach ($response["tracks"]["items"] as $i => $track) {

            $tracks[] = $this->format_track($track);

        }

        return $tracks;

    }

    /**
     * Get Track
     *
     * Get Spotify catalog information for a single track identified by its unique Spotify ID.
     *
     * @param string $id
     *
     * @return array
     */
    public function track(string $id): array {

        $this->log("Track info", "C");

        if (preg_match("/^spotify:(.+?):(.+)$/", $id, $uri)) {

            $id = $uri[2];

        }

        $this->curl_options([
            "url" => "https://api.spotify.com/v1/tracks/{$id}",
            "authorization" => "bearer",
            "customrequest" => "GET"
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        if (!$this->curl_response_class(2)) return [];

        return $this->format_track($response);

    }

    /**
     * Format track data into general shape for global usage.
     * Data may be in different locations depending on scenario.
     *
     * @param array $data
     *
     * @return array
     */
    private function format_track(array $data): array {

        $item = $data;
        if (!empty($data["item"])) $item = $data["item"];
        if (!empty($data["track"])) $item = $data["track"];

        $artists = [];

        foreach ($item["artists"] as $artist) {

            $artists[] = [
                "uri" => $artist["uri"],
                "title" => $artist["name"]
            ];

        }

        return [
            "track" => [
                "uri" => $item["uri"],
                "title" => $item["name"]
            ],
            "album" => [
                "uri" => $item["album"]["uri"],
                "title" => $item["album"]["name"]
            ],
            "artists" => $artists,
            "released" => strtotime($item["album"]["release_date"]),
            "cover" => $this->format_cover($item["album"]["images"]),
            "position" => $data["progress_ms"] ?? 0,
            "duration" => $item["duration_ms"],
            "progress" => ($data["progress_ms"] ?? 0) * 100 / $item["duration_ms"],
            "played" => isset($data["played_at"]) ? strtotime($data["played_at"]) : null,
            "paused" => isset($data["is_playing"]) ? !$data["is_playing"] : null
        ];

    }

    /**
     * Convert artist data into wanted format for convenient usage.
     *
     * @param array $data
     * @param string $format
     *
     * @return string
     */
    public function format_artists(array $data, string $format = "none"): string {

        $artists = [];

        foreach ($data as $artist) {

            $artists[] = match ($format) {

                "href" => "<a href=\"{$this->uri2url($artist["uri"])}\" target=\"_blank\">{$artist["title"]}</a>",
                "slack" => "<{$this->uri2url($artist["uri"])}|{$artist["title"]}>",
                default => $artist["title"]

            };

        }

        return implode(", ", $artists);

    }

    /**
     * Convert milliseconds into representative minutes and seconds format.
     *
     * @param int $milliseconds
     *
     * @return string
     */
    public function format_duration(int $milliseconds): string {

        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return "{$minutes} min {$seconds} s";

    }

    /**
     * Get cover image in the closest desired size.
     *
     * @param array $data
     * @param int $max_size
     *
     * @return string
     */
    private function format_cover(array $data, int $max_size = 640): string {

        foreach ($data as $image) {

            if ($image["width"] > $max_size || $image["height"] > $max_size) continue;

            return $image["url"];

        }

        return "";

    }

    /**
     * Convert Spotify URI into URL.
     *
     * @param string $uri
     *
     * @return string
     */
    public function uri2url(string $uri): string {

        preg_match("/^(spotify):(.+?):(.+)$/", $uri, $uri);

        return "https://open.spotify.com/{$uri[2]}/{$uri[3]}";

    }

    /**
     * Handle cURL options by user parameters.
     *
     * @param array $parameters
     *
     * @return bool
     */
    private function curl_options(array $parameters = []): bool {

        curl_reset($this->curl);

        $options = [
            CURLOPT_URL => $parameters["url"],
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_RETURNTRANSFER => true
        ];

        if (!empty($parameters["post"])) {

            $options[CURLOPT_POST] = true;

        }

        if (!empty($parameters["customrequest"])) {

            $options[CURLOPT_CUSTOMREQUEST] = $parameters["customrequest"];

        }

        if (!empty($parameters["postfields"])) {

            $options[CURLOPT_POSTFIELDS] = http_build_query($parameters["postfields"]);

        }

        if (!empty($parameters["authorization"])) {

            switch ($parameters["authorization"]) {

                case "basic":
                    $options[CURLOPT_HTTPHEADER][] = "Authorization: Basic " . base64_encode(SPOTIFY_CLIENT_ID . ":" . SPOTIFY_CLIENT_SECRET);
                    break;

                case "bearer":
                    $options[CURLOPT_HTTPHEADER][] = "Authorization: Bearer {$this->access_token}";
                    break;

            }

        }

        return curl_setopt_array($this->curl, $options);

    }

    /**
     * Get cURL response main class because Spotify uses variety of specific codes.
     *
     * @param int $class
     *
     * @return bool
     */
    private function curl_response_class(int $class): bool {

        return preg_match("/^{$class}[0-9]{2}$/", curl_getinfo($this->curl, CURLINFO_HTTP_CODE));

    }

    /**
     * Log some stuff while debugging.
     *
     * Levels:
     * - I Info     General verbose output
     * - C Calls    API calls
     * - N Notice   Something worth notice, minor problem or possible error
     * - W Warning  Stuff about to hit the fan!
     * - F Fatal    Oh we dead x(
     *
     * @param string|array $stuff
     * @param string $level
     * @param bool $time
     * @param bool $type
     *
     * @return bool
     */
    public function log(string|array $stuff, string $level = "N", bool $time = true, bool $type = true): bool {

        preg_match_all("/[ICNWF]/", SPOTIFYBATON_LOG_LEVEL, $levels);

        if (!in_array($level, $levels[0])) {

            return false;

        }

        $contents = [];

        if ($time) {

            $contents[] = date(SPOTIFYBATON_LOG_DT, time());

        }

        if ($type) {

            $contents[] = "[{$level}]";

        }

        $contents[] = get_class($this) . ":";

        $contents[] = is_array($stuff) ? print_r($stuff, true) : $stuff;

        return boolval(file_put_contents(SPOTIFYBATON_LOG, implode(" ", $contents) . PHP_EOL, FILE_APPEND | LOCK_EX));

    }

}