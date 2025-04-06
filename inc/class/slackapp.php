<?php

class SlackApp extends SpotifyBaton {

    private array $session, $action, $payload, $request;

    public function __construct() {

        parent::__construct();

        $this->session = json_decode(file_get_contents(SLACK_SESSION), true);

        if (!empty($payload = filter_input(INPUT_POST, "payload"))) {

            $this->handle_action(json_decode($payload, true));

        }

        if (!empty($_REQUEST['command']) && preg_match("/^\/(.+)$/", $_REQUEST['command'], $command)) {

            $this->handle_command($command[1], $_REQUEST);

        }

    }

    public function __destruct() {

        parent::__destruct();

        file_put_contents(SLACK_SESSION, json_encode($this->session, JSON_PRETTY_PRINT));

    }

    private function command_channel(): array {

        $blocks = [$this->block_header("Manage channels", "house_with_garden")];

        if (!$this->is_operator()) {

            $blocks[] = $this->block_mrkdwn("You need to be operator for this command! :neutral_face:");

            return $blocks;

        }

        if (preg_match("/^(.+?) #(.+)$/", $this->request["text"], $command)) {

            if (!empty($channel = $this->slack_find_channel($command[2]))) {

                if (empty($this->session["channels"])) {

                    $this->session["channels"] = [];

                }

                switch ($command[1]) {

                    case "add":

                        if (in_array($channel, $this->session["channels"])) {

                            $blocks[] = $this->block_mrkdwn("Already in <#{$channel}> :face_with_monocle:");

                        } else {

                            $this->session["channels"][] = $channel;

                            $this->slack_post("conversations.join", [
                                "channel" => $channel
                            ]);

                            $blocks[] = $this->block_mrkdwn("Joined in <#{$channel}> :saluting_face:");

                        }

                        break;

                    case "del":

                        if (($index = array_search($channel, $this->session["channels"])) !== false) {

                            unset($this->session["channels"][$index]);

                            $this->slack_post("conversations.leave", [
                                "channel" => $channel
                            ]);

                            $blocks[] = $this->block_mrkdwn("Left <#{$channel}> :saluting_face:");

                        } else {

                            $blocks[] = $this->block_mrkdwn("I'm not in <#{$channel}> :face_with_monocle:");

                        }

                        break;

                    default:

                        $blocks[] = $this->block_mrkdwn("You want me to do what with <#{$channel}> :face_with_raised_eyebrow:");

                        break;

                }

            } else {

                $blocks[] = $this->block_mrkdwn("Channel not found :face_with_monocle:");

            }

        } else {

            $blocks[] = $this->block_mrkdwn("Something went horribly wrong! :face_with_peeking_eye:");

        }

        return $blocks;

    }

    private function command_operator(): array {

        $blocks = [$this->block_header("Manage operators", "identification_card")];

        if (!$this->is_operator()) {

            $blocks[] = $this->block_mrkdwn("You need to be operator for this command! :neutral_face:");

            return $blocks;

        }

        if (preg_match("/^(.+?) @(.+)$/", $this->request["text"], $command)) {

            if (!empty($user = $this->slack_find_user($command[2]))) {

                if (empty($this->session["operators"])) {

                    $this->session["operators"] = [];

                }

                switch ($command[1]) {

                    case "add":

                        if (in_array($user, $this->session["operators"])) {

                            $blocks[] = $this->block_mrkdwn("<@{$user}> already operator! :face_with_monocle:");

                        } else {

                            $this->session["operators"][] = $user;

                            $blocks[] = $this->block_mrkdwn("<@{$user}> promoted to operator :saluting_face:");

                        }

                        break;

                    case "del":

                        if (($index = array_search($user, $this->session["operators"])) !== false) {

                            unset($this->session["operators"][$index]);

                            $blocks[] = $this->block_mrkdwn("<@{$user}> removed from operators :saluting_face:");

                        } else {

                            $blocks[] = $this->block_mrkdwn("<@{$user}> is not an operator! :face_with_monocle:");

                        }

                        break;

                    default:

                        $blocks[] = $this->block_mrkdwn("You want me to do what with <@{$user}> :face_with_raised_eyebrow:");

                        break;

                }

            } else {

                $blocks[] = $this->block_mrkdwn("User not found :face_with_monocle:");

            }

        } else {

            $blocks[] = $this->block_mrkdwn("Something went horribly wrong! :face_with_peeking_eye:");

        }

        return $blocks;

    }

    private function command_voteskip(): array {

        $item = $this->player_current();

        $blocks = [$this->block_header("Vote skip", "mega")];

        if (empty($item)) {

            $blocks[] = $this->block_mrkdwn("There are no playback going on for this action :face_with_monocle:");

            return $blocks;

        }

        $blocks[] = $this->block_track($item);

        $blocks[] = $this->block_divider();

        $blocks[] = [
            "type" => "actions",
            "elements" => [
                $this->block_button("Start", "voteskip_start", $item['track']['uri'], "primary"),
                $this->block_button("Cancel", "voteskip_cancel", null, "danger")
            ]
        ];

        return $blocks;

    }

    private function action_voteskip_start() {

        $this->session['voteskip'] = [
            "created" => time(),
            "expires" => strtotime("+1 minute"),
            "ts" => "",
            "uri" => $this->action['value'],
            "votes" => [
                "yes" => 0,
                "no" => 0,
                "users" => []
            ]
        ];

        $this->slack_response($this->payload['response_url'], [
            "delete_original" => true
        ]);

        $this->action_voteskip();

    }

    private function action_voteskip_cancel() {

        unset($this->session['voteskip']);

        $this->slack_response($this->payload['response_url'], [
            "delete_original" => true
        ]);

    }

    private function action_voteskip() {

        $item = $this->track($this->session['voteskip']['uri']);

        $blocks = [$this->block_header("Vote skip", "mega")];

        $blocks[] = $this->block_track($item);

        $blocks[] = $this->block_divider();

        // Vote expired
        if ($this->session['voteskip']['expires'] < time()) {

            $blocks[] = [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*Vote skip expired before there were enough votes*"
                ]
            ];

            $this->slack_post("chat.update", [
                "channel" => $this->payload['channel']['id'],
                "ts" => $this->session['voteskip']['ts'],
                "blocks" => $blocks
            ]);

            return false;

        }

        // Song changed
        if ($this->session['voteskip']['uri'] != $this->player_current()['track']['uri']) {

            $blocks[] = [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*Song changed before vote results*"
                ]
            ];

            $this->slack_post("chat.update", [
                "channel" => $this->payload['channel']['id'],
                "ts" => $this->session['voteskip']['ts'],
                "blocks" => $blocks
            ]);

            return false;

        }

        #$this->session['voteskip']['votes']['users'][$this->payload['user']['id']] = $this->action['value'];
        $this->session['voteskip']['votes']['users'][] = $this->action['value'];
        $this->session['voteskip']['votes']['yes'] = count(array_filter($this->session['voteskip']['votes']['users'], fn($user) => $user === "yes"));
        $this->session['voteskip']['votes']['no'] = count(array_filter($this->session['voteskip']['votes']['users'], fn($user) => $user === "no"));

        // Vote passed
        if ($this->session['voteskip']['votes']['yes'] >= 5) {

            $this->player_next();

            $blocks[] = $this->block_mrkdwn("*Skipped by vote*");

            $this->slack_post("chat.update", [
                "channel" => $this->payload['channel']['id'],
                "ts" => $this->session['voteskip']['ts'],
                "blocks" => $blocks
            ]);

            return false;

        }

        // Vote did not pass
        if ($this->session['voteskip']['votes']['no'] >= 5) {

            $blocks[] = $this->block_mrkdwn("*Remains by vote*");

            $this->slack_post("chat.update", [
                "channel" => $this->payload['channel']['id'],
                "ts" => $this->session['voteskip']['ts'],
                "blocks" => $blocks
            ]);

            return false;

        }

        // Vote blocks
        $blocks[] = $this->block_mrkdwn("*Vote skip currently playing song* (expires at " . date("H.i", strtotime("+1 minute")) . ")");

        $blocks[] = [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => str_repeat(":large_green_square:", $this->session['voteskip']['votes']['yes']) . str_repeat(":black_large_square:", 5- $this->session['voteskip']['votes']['yes'])
            ],
            "accessory" => [
                "type" => "button",
                "style" => "primary",
                "text" => [
                    "type" => "plain_text",
                    "emoji" => true,
                    "text" => "Yes"
                ],
                "action_id" => "voteskip",
                "value" => "yes"
            ]
        ];

        $blocks[] = [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => str_repeat(":large_red_square:", $this->session['voteskip']['votes']['no']) . str_repeat(":black_large_square:", 5 - $this->session['voteskip']['votes']['no'])
            ],
            "accessory" => [
                "type" => "button",
                "style" => "danger",
                "text" => [
                    "type" => "plain_text",
                    "emoji" => true,
                    "text" => "No"
                ],
                "action_id" => "voteskip",
                "value" => "no"
            ]
        ];

        if (empty($this->session['voteskip']['ts'])) {

            // Start vote
            $response = $this->slack_post("chat.postMessage", [
                "channel" => $this->payload['channel']['id'],
                "blocks" => $blocks,
                "unfurl_links" => false,
                "unfurl_media" => false
            ]);

            if (!empty($response['ts'])) {

                $this->session['voteskip']['ts'] = $response['ts'];

            }

        } else {

            // Update vote
            $this->slack_post("chat.update", [
                "channel" => $this->payload['channel']['id'],
                "ts" => $this->session['voteskip']['ts'],
                "blocks" => $blocks
            ]);

        }

        return true;

    }

    private function command_np(): array {

        $item = $this->player_current();

        $blocks = [$this->block_header("Now playing", "loud_sound")];

        if (empty($item)) {

            $blocks[] = $this->block_mrkdwn("There are no playback going on for this action :face_with_monocle:");

            return $blocks;

        }

        $blocks[] = $this->block_track($item);

        return $blocks;

    }

    private function command_remote(): array {

        $blocks = [$this->block_header("Remote control", "satellite_antenna")];

        if (!$this->is_operator()) {

            $blocks[] = $this->block_mrkdwn("You need to be operator for this command! :neutral_face:");

            return $blocks;

        }

        $blocks[] = [
            "type" => "actions",
            "elements" => [
                $this->block_button("Previous", "remote_prev"),
                $this->block_button("Play", "remote_play"),
                $this->block_button("Pause", "remote_pause"),
                $this->block_button("Next", "remote_next"),
                $this->block_button("Close", "remote_close", null, "danger")
            ]
        ];

        return $blocks;

    }

    private function action_remote_prev() {

        $this->player_previous();

    }

    private function action_remote_play() {

        $this->player_play();

    }

    private function action_remote_pause() {

        $this->player_pause();

    }

    private function action_remote_next() {

        $this->player_next();

    }

    private function action_remote_close() {

        $this->slack_response($this->payload['response_url'], [
            "delete_original" => true
        ]);

    }

    private function command_track(): array {

        $items = $this->search($this->request['text']);

        $blocks = [$this->block_header("Search track", "mag")];

        if (empty($items)) {

            $blocks[] = $this->block_mrkdwn("Didn't found any tracks :sob:");

            return $blocks;

        }

        foreach ($items as $item) {

            $blocks[] = $this->block_track($item);

            $blocks[] = [
                "type" => "actions",
                "elements" => [
                    $this->block_button("Add to queue", "player_queue", $item['track']['uri'], "primary"),
                    $this->block_button("Share to channel", "track_share", $item['track']['uri'])
                ]
            ];

            $blocks[] = $this->block_divider();

        }

        $blocks[] = [
            "type" => "actions",
            "elements" => [
                $this->block_button("Close search", "search_close", null, "danger")
            ]
        ];

        return $blocks;

    }

    private function action_player_queue() {

        $item = $this->track($this->action['value']);

        $this->player_queue($item["track"]["uri"]);

        $blocks = [$this->block_header("Track queued", "loud_sound")];

        $blocks[] = $this->block_track($item);

        $this->slack_post("chat.postEphemeral", [
            "channel" => $this->payload["channel"]["id"],
            "user" => $this->payload["user"]["id"],
            "blocks" => json_encode($blocks)
        ]);

    }

    private function action_track_share() {

        $item = $this->track($this->action['value']);

        $blocks = [$this->block_header("Track shared", "loud_sound")];

        $blocks[] = $this->block_mrkdwn("<@{$this->payload["user"]["id"]}> wants you to listen this!");;

        $blocks[] = $this->block_track($item);

        $this->slack_post("chat.postMessage", [
            "channel" => $this->payload["channel"]["id"],
            "user" => $this->payload["user"]["id"],
            "blocks" => json_encode($blocks),
            "unfurl_links" => false,
            "unfurl_media" => false
        ]);

    }

    private function action_search_close() {

        $this->slack_response($this->payload['response_url'], [
            "delete_original" => true
        ]);

    }

    private function handle_action(array $payload) {

        foreach ($payload['actions'] as $action) {

            if (method_exists(get_class($this), "action_{$action['action_id']}")) {

                $this->payload = $payload;
                $this->action = $action;

                $this->{"action_{$action['action_id']}"}();

            }

        }

    }

    private function handle_command(string $command, array $request) {

        if (method_exists(get_class($this), "command_{$command}")) {

            $this->request = $request;

            if (!empty($blocks = $this->{"command_{$command}"}())) {

                $this->slack_post("chat.postEphemeral", [
                    "channel" => $this->request["channel_id"],
                    "user" => $this->request["user_id"],
                    "blocks" => $blocks
                ]);

            }

        }

    }

    private function is_operator(): bool {

        if (empty($this->request["user_id"])) {

            // Something fishy going on, deny everything!
            return false;

        }

        if (empty($this->session["operators"])) {

            // There are no operators, so everyone is!
            return true;

        }

        if (in_array($this->request["user_id"], $this->session["operators"])) {

            return true;

        }
/*
        foreach ($this->session["operators"] as $operator) {

            if ($operator === $this->request["user_id"]) {

                return true;

            }

        }
*/
        return false;

    }

    private function in_channel(string $channel): bool {

        if (empty($this->session["channels"])) {

            // Channels not defined so it's FFA
            return true;

        }

        if (in_array($channel, $this->session["channels"])) {

            return true;

        }

        return false;

    }

    private function slack_find_user(string $username): string {

        if (empty($this->session["userslist"]["created"]) || $this->session["userslist"]["created"] < strtotime("-10 minutes")) {

            $users = $this->slack_post("users.list", []);

            if (!empty($users["members"])) {

                $this->session["userslist"] = [
                    "created" => time(),
                    "users" => []
                ];

                foreach ($users["members"] as $user) {

                    $this->session["userslist"]["users"][$user["id"]] = $user["name"];

                }

            }

        }

        if (!empty($this->session["userslist"]["users"])) {

            foreach ($this->session["userslist"]["users"] as $id => $name) {

                if ($name === $username) {

                    return $id;

                }

            }

        }

        return "";

    }

    private function slack_find_channel(string $channelname): string {

        if (empty($this->session["conversationslist"]["created"]) || $this->session["conversationslist"]["created"] < strtotime("-10 minutes")) {

            $channels = $this->slack_post("conversations.list", [
                "types" => "public_channel,private_channel",
                "limit" => 1000
            ]);

            if (!empty($channels["channels"])) {

                $this->session["conversationslist"] = [
                    "created" => time(),
                    "channels" => []
                ];

                foreach ($channels["channels"] as $channel) {

                    $this->session["conversationslist"]["channels"][$channel["id"]] = $channel["name"];

                }

            }

        }

        if (!empty($this->session["conversationslist"]["channels"])) {

            foreach ($this->session["conversationslist"]["channels"] as $id => $name) {

                if ($name === $channelname) {

                    return $id;

                }

            }

        }

        return "";

    }

    private function slack_response(string $url, array $content): array {

        curl_reset($this->curl);

        curl_setopt_array($this->curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json; charset=utf-8"
            ],
            CURLOPT_POSTFIELDS => json_encode($content)
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        $this->debug($response);

        return $response;

    }

    private function slack_post(string $endpoint, array $payload): array {

        curl_reset($this->curl);

        curl_setopt_array($this->curl, [
            CURLOPT_URL => "https://slack.com/api/{$endpoint}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json; charset=utf-8",
                "Authorization: Bearer " . SLACK_APP_TOKEN
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = json_decode(curl_exec($this->curl), true);

        $this->debug($response);

        return $response;

    }

    private function block_header(string $title, string $icon): array {

        return [
            "type" => "header",
            "text" => [
                "type" => "plain_text",
                "text" => ":{$icon}: {$title}",
                "emoji" => true
            ]
        ];

    }

    private function block_button(string $text, string $action, string $value = null, string $style = null): array {

        $block = [
            "type" => "button",
            "text" => [
                "type" => "plain_text",
                "emoji" => true,
                "text" => $text
            ],
            "action_id" => $action,
            "value" => $value ?: uniqid()
        ];

        if (!empty($style)) {

            $block["style"] = $style;

        }

        return $block;

    }

    private function block_mrkdwn(string $text): array {

        return [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => $text
            ]
        ];

    }

    private function block_track(array $item): array {

        return [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => implode("\n", [
                    "*<{$this->uri2url($item['track']['uri'])}|{$item['track']['title']}>*",
                    ":cd: <{$this->uri2url($item['album']['uri'])}|{$item['album']['title']}>",
                    ":speaking_head_in_silhouette: {$this->format_artists($item['artists'], "slack")}",
                    ":calendar: " . date("j.n.Y", $item['released'])
                ])
            ],
            "accessory" => [
                "type" => "image",
                "image_url" => $item['cover'],
                "alt_text" => "Album cover"
            ]
        ];

    }

    private function block_divider(): array {

        return [
            "type" => "divider"
        ];

    }

}