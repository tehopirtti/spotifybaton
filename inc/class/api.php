<?php

class API extends SpotifyBaton {

    private string $request_method = "";

    private array $parameters = [];
    private array $response = [];

    public function __construct() {

        parent::__construct();

        $this->request_method = $_SERVER["REQUEST_METHOD"] ?? "GET";

        if (!empty($_SERVER["QUERY_STRING"]) && $this->request_method === "GET") {

            parse_str($_SERVER["QUERY_STRING"], $this->parameters);

        } else {

            $this->parameters = json_decode(file_get_contents("php://input"), true) ?? [];

        }

        if ($this->request_method === "GET") {

            $this->response = $this->player_current();

        }

        if ($this->request_method === "POST") {

            // oh dear...

            if (
                empty($_SERVER["CONTENT_TYPE"]) ||
                $_SERVER["CONTENT_TYPE"] != "application/json" ||
                empty($_SERVER["HTTP_ACCEPT"]) ||
                $_SERVER["HTTP_ACCEPT"] != "application/json" ||
                empty($_SERVER["HTTP_AUTHORIZATION"]) ||
                !preg_match("/^Bearer (.{32,60})$/", $_SERVER["HTTP_AUTHORIZATION"], $token) ||
                empty($token[1]) ||
                $token[1] != SPOTIFYBATON_API_TOKEN
            ) {

                return false;

            };

            if (!empty($this->parameters["action"])) {

                match ($this->parameters["action"]) {

                    "play" => $this->player_play(),
                    "pause" => $this->player_pause(),
                    "next" => $this->player_next(),
                    "previous" => $this->player_previous(),
                    default => false

                };

            }

        }

        return true;

    }

    public function __destruct() {

        http_response_code(200);

        if (!empty($this->response)) {

            print json_encode($this->response, JSON_PRETTY_PRINT);

        }

    }

}