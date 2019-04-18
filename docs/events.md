“Events”
========

Tinyboard’s “events” are similar to those in Javascript. Events handlers are anonymous functions that are registered to Tinyboard. When Tinyboard carries out an action such as deleting a file, generating a tripcode or checking whether an IP address is banned, you can interfere with the response or make Tinyboard do something else at the same time. You can bind an infinite amount of handlers to an event.

If you’re familiar with jQuery, you can think of Tinyboard’s `event_handler` function as jQuery’s `bind`.

If event handlers return a string or true it will interupt the action. See examples below.

Anonymous functions
-------------------
Anonymous functions are only available in PHP 5.3.0+. However, you can instead use real functions as event handlers if you don’t meet this requirement.

Examples
--------
To disallow a certain filename when posting (for whatever reason):
```php
event_handler('post', function($post) {
	if ($post->has_file && $post->filename == 'bad_filename.png')
		return 'You cannot post with that filename';
});
```
To force all posts to have a certain tripcode:
```php
event_handler('post', function($post) {
	$post->trip = '!hello.world';
});
```
To trick Tinyboard into thinking a specific thread is locked:
```php
event_handler('check-locked', function($id) {
	if ($id == 50)
		return true;
});
```
To disallow a thread from being bumped:
```php
event_handler('bump', function($id) {
	if ($id == 50)
		return true;
});
```
To create a custom “poster ID” for a specific IP address:
```php
event_handler('poster-id', function($ip, $thread) {
	if ($ip == '4.3.2.1')
		return 'Hello';
});
```

Events
------
Event                 | Called                                             | Parameters                  | Returned value
--------------------- | -------------------------------------------------- | --------------------------- | --------------
**post**              | Before a new post is entered in the database.      | `object $post`              | If not false, the post is rejected and the returned value is displayed as an error message.
**post-after**        | After a new post is entered in the database.       | `array $post`               | —
**write**             | After a file has been written to.                  | `string $path`              | —
**unlink**            | After a file has been deleted.                     | `string $path`              | —
**check-flood**       | Before checking if a poster is flooding the board. | `array $post`               | If true, the poster will be deemed malicious regardless of Tinyboard’s decision.
**check-locked**      | Before checking if a thread is locked.             | `int $id`                   | If true, Tinyboard will treat the thread as being locked.
**check-sage-locked** | Before checking if a thread is “sage-locked”.      | `int $id`                   | If true, Tinyboard will treat the thread as being “sage-locked”.
**bump**              | Before bumping a thread.                           | `int $id`                   | If true, the thread will not be bumped.
**check-robot**       | Before checking if a post is duplicate (for R9K).  | `string $body`              | If true, the poster will be muted regardless of whether or not the content was original to Tinyboard.
**mute-time**         | Before fetching current mute time for a user.      | —                           | If > 0, Tinyboard will use the returned value as the length of the mute (in seconds).
**build-thread**      | Before building a thread.                          | `int $id`                   | If true, the thread `.html` file will not be (re)created.
**poster-id**         | Before generating a poster ID.                     | `string $ip`, `int $thread` | If not false, Tinyboard will use the returned value as the poster ID instead.
**tripcode**          | Before generating a tripcode.                      | `string $name`              | The returned value will be used as the name/tripcode combination instead. Must be in the format of `array(name, tripcode)`.

See also
--------
* [Custom flood filters](config/flood_filters.md)
