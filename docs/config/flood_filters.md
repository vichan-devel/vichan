Custom Flood Filters
====================

Custom flood filters detect known types of attacks and reject posts accordingly. They are made up of a **condition** and an **action**, for when all conditions are met.

Conditions
----------
These condition names are case-insensitive. All conditions are optional. If you don’t care about a particular value, don’t include it in your filter.

### ~~Posts in past X minutes~~
Condition                                     | Format
--------------------------------------------- | ------
~~posts_in_past_x_minutes~~                   | array(`x`,`y`)
~~threads_with_no_replies_in_past_x_minutes~~ | array(`x`,`y`)

~~Checks if there has been `x` posts or more in the past `y` minutes (on the current board).~~

The above conditions are currently unavailable in the latest git revision.

### Input
Condition | Format
--------- | ------
name      | Regexp
trip      | String
email     | Regexp
subject   | Regexp
body      | Regexp
filename  | Regexp
extension | Regexp
ip        | Regexp
OP        | Boolean
has_file  | Boolean

### Custom
Custom | Format
------ | ------
custom | Function

Similar to [events](../events.md) but the function must return true if the condition was met.
```php
$config['flood_filters'][] = array(
	'condition' => array(
		'name' => '/^Anonymous$/',
		'body' => '/h$/i',
		'OP' => false,
		'custom' => function($post) {
			if ($post['name'] == 'Anonymous')
				return true;
			else
				return false;
		}
	),
	'action' => 'reject'
);
```

Actions
-------

### Reject
Variable | Type     | Format | Default
-------- | -------- | ------ | -------
message  | Optional | String | `"Posting throttled by flood filter."`

The post is rejected and an error message is displayed.

### Ban
Variable   | Type     | Format  | Default          | Description
---------- | -------- | ------- | ---------------- | -----------
reason     | Required | String  | —                | The displayed ban reason.
expires    | Optional | Integer | `0` (indefinite) | The ban length in seconds.
reject     | Optional | Boolean | `true`           | Whether to allow the post before banning.
message    | Optional | String  | `NULL`           | If defined, display an error instead of the ban page.
all_boards | Optional | Boolean | `false`          | Whether to make the ban global.

The user is immediately banned and the post may be rejected.

Example
-------
The following example, to be added in a configuration file, blocks users posting a reply with the name “surgeon”, ending his posts with “regards, the surgeon” or similar.
```php
$config['flood_filters'][] = array(
	'condition' => array(
		'name' => '/^surgeon$/',
		'body' => '/regards,\s+(the )?surgeon$/i',
		'OP' => false
	),
	'action' => 'reject',
	'message' => 'Go away, spammer.'
);
```
Instead of rejecting the post, you can ban the user too.
```php
$config['flood_filters'][] = array(
	'condition' => array(
		'name' => '/^surgeon$/',
		'body' => '/regards,\s+(the )?surgeon$/i',
		'OP' => false
	),
	'action' => 'ban',
	'expires' => 60 * 60 * 3, // 3 hours
	'reason' => 'Go away, spammer.'
);
```

See also
--------
* [Events](../events.md)
