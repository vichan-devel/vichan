Boardlinks
==========

For lack of a better name, “boardlinks” are those sets of navigational links that appear at the top and bottom of board pages. They can be a list of links to boards and/or other pages such as status blogs and social network profiles.

“Groups” in the boardlinks are marked with square brackets. Tinyboard allows for infinite recursion with groups. Each `array()` in `$config['boards']` represents a new square bracket group.

Custom links can be defined by using `key => value` pairs.

Examples:
```php
// [ a / b / c ]
$config['boards'] = array('a', 'b', 'c');

// [ a ] [ b / c ] [ d / e ] [ home ]
$config['boards'] = array(
	array('a'),
	array('b', 'c'),
	array('d', 'e'),
	array('home' => '/')
);

// PHP 5.4.0+ syntax
$config['boards'] = [ ['a'], ['b', 'c'], ['d', 'e'], ['home' => '/'] ];
```
