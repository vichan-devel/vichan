Capcodes
========

A “capcode” may be used by staff to attribute the post to `Anonymous ## Mod` (and alike). To use a moderator capcode (only available in the moderator interface), simply append `## Mod` to your name and/or tripcode with a space before it. A common mistake when trying to use a capcode is leaving out the preceding space; that is required to seperate it from a standard secure tripcode.

Administrators may use anything, such as “Admin” or “God”, as a capcode.

Custom capcodes
---------------
It is possible to configure certain or all capcodes to display differently.
```php
// Change "## Mod" to make everything purple, including the name and tripcode
$config['custom_capcode']['Mod'] = array(
	'<span class="capcode" style="color:purple"> ## %s</span>',
	'color:purple', // Change name style; optional
	'color:purple' // Change tripcode style; optional
);
```
