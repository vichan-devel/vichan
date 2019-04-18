Word Filters
============

A wordfilter (sometimes referred to as just a “filter” or “censor”) automatically scans users’ posts as they are submitted and changes or censors particular words or phrases.

Standard replacement
--------------------
```php
$config['wordfilters'][] = array('cat', 'dog');
```

Regular expressions
-------------------
Using Perl Compatible Regular Expressions, you can match more words or phrases, even if they aren’t exact. To use regular expression wordfilters, you must include ‘true’ as the third element in the array. The following example changes both “cat” and “car” to “dog”.
```php
$config['wordfilters'][] = array('/ca[rt]/', 'dog', true);
```
