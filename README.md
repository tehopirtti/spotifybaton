![SpotifyBaton](https://raw.githubusercontent.com/tehopirtti/spotifybaton/refs/heads/master/inc/img/logo.png)
# Usable methods so far
## Get Recently Played Tracks
```
player_history(limit, reverse)
```
Get tracks from the current user's recently played tracks. Note: Currently doesn't support podcast episodes.

| Parameter | Type    | Default |
|-----------|---------|---------|
| limit     | integer | 3       |
| reverse   | boolean | false   |
## Get Currently Playing Track
```
player_current
```
Get the object currently being played on the user's Spotify account.
## Get the User's Queue
```
player_upcoming(limit, reverse)
```
Get the list of objects that make up the user's queue.

| Parameter | Type    | Default |
|-----------|---------|---------|
| limit     | integer | 3       |
| reverse   | boolean | true    |
## Add Item to Playback Queue
```
player_queue(uri)
```
Add an item to the end of the user's current playback queue.  This API only works for users who have Spotify Premium. The order of execution is not guaranteed when you use this API with other Player API endpoints.

| Parameter | Type   | Default |
|-----------|--------|---------|
| uri       | string |         |
## Start/Resume Playback
```
player_play
```
Start a new context or resume current playback on the user's active device. This API only works for users who have Spotify Premium. The order of execution is not guaranteed when you use this API with other Player API endpoints.
## Pause Playback
```
player_pause
```
Pause playback on the user's account. This API only works for users who have Spotify Premium. The order of execution is not guaranteed when you use this API with other Player API endpoints.
## Skip To Next
```
player_next
```
Skips to next track in the user’s queue. This API only works for users who have Spotify Premium. The order of execution is not guaranteed when you use this API with other Player API endpoints.
## Skip To Previous
```
player_previous
```
Skips to previous track in the user’s queue. This API only works for users who have Spotify Premium. The order of execution is not guaranteed when you use this API with other Player API endpoints.
## Search for Item
```
search(query, limit, type)
```
Get Spotify catalog information about albums, artists, playlists, tracks, shows, episodes or audiobooks that match a keyword string. Audiobooks are only available within the US, UK, Canada, Ireland, New Zealand and Australia markets.

| Parameter | Type    | Default |
|-----------|---------|---------|
| query     | string  |         |
| limit     | integer | 3       |
| type      | string  | track   |
## Get Track
```
track(id)
```
Get Spotify catalog information for a single track identified by its unique Spotify ID.

| Parameter | Type    | Default |
|-----------|---------|---------|
| id        | string  |         |
> Spotify URI can be also given as parameter, from which ID is extracted.