# WTF?
SpotifyBaton is a PHP class to conduct Spotify in various ways and this is it's SlackApp extension to bring it's control into Slack users.

From now on, in this readme, SpotifyBaton will refer to the actual Slack application.
# Usage
## Channels
Channels are not mandatory and their main purpose is only to enable usage restrictions. If SpotifyBaton is "added" (not invited) into any channel, it may be only used in those channels it's added to - otherwise it's usable from anywhere.  Note that channel restrictions doesn't work on private channels since Slack doesn't list private channels in its `conversations.list` endpoint! Once SpotifyBaton is added into any channel, it'll invite itself into that channel and will only work from there where it's "allowed" (read "added") to.
```
/channel add|del #channel
```
<sup>_* If operators are enabled, user must be one to perform this command!_</sup>

Adding and removing SpotifyBaton will be notified to user by private message.
## Operators
Also operators are optional - without any, all commands are available for everybody (which is probably unwanted situation). First announced operator can be kinda thought of initialization. 
```
/operator add|del @user
```
<sup>_* Command can be used only by operators once first one is set!_</sup>
## Vote skip
Shows preview before actual vote, where user can start or cancel it.
```
/voteskip
```
There are some rules in the voting system:
- every user can vote only once for yes or no (vote can be changed)
- needs five yes or no votes to perform any action
- expires in one minute
## Track search and queue
Search tracks from Spotify by given keywords and shows top three results. From results user can add track into queue or share it into current channel.
```
/track <search term>
```
## Now playing
Shows information about currently playing track.
```
/np
```
## Remote control
This is pretty straightforward and really doesn't need any further introduction.
```
/remote
```
<sup>_* Command can be used only by operators (if set)_</sup>
# Needed scopes
Ugh.... a lot, I guess.