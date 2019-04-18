Markup Syntax
=============

Since v0.9.6, Tinyboard’s markup syntax has become customizable. `$config['markup']` is an array of markup rules. Each markup rule is an array containing a regular expression for matching the text, and a replacement which can either be a string (using `$*` references), or a callback.

The input has already been HTML-escaped.

Defaults
--------

### Code
```php
$config['markup'][] = array("/'''(.+?)'''/", "<strong>\$1</strong>");
$config['markup'][] = array("/''(.+?)''/", "<em>\$1</em>");
$config['markup'][] = array("/\*\*(.+?)\*\*/", "<span class=\"spoiler\">\$1</span>");
$config['markup'][] = array("/^\s*==(.+?)==\s*$/m", "<span class=\"heading\">\$1</span>");
```

### Translation
(Of course this isn’t exactly how it will look.)

Input           | Output
--------------- | ------
`''text''`      | `<em>text</em>`
`'''text'''`    | `<strong>text</strong>`
`**text**`      | `<span style="background:#000000;color:#FFFFFF;padding:2px;">text</span>`
`==text==`      | `<span style="font-size:2em;font-weight:bold;">text</span>`

Examples
--------

### Standard
```php
$config['markup'][] = array("/&lt;3/", "&hearts;"); 
```
Input | Output
----- | ------
`<3`  | ♥

### Callback
Callbacks can be used for more advanced markup.
```php
$config['markup'][] = array(
	'/MD5&lt;(.+)&gt;/',
	function($matches) {
		return md5($matches[1]);
	}
);
```
Input       | Output
----------- | ------
`MD5<test>` | 098f6bcd4621d373cade4e832627b4f6

See also
--------
* [Configuration Basics](../config.md)
