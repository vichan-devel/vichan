Cache
=====

Static files
------------
You may have noticed that Tinyboard uses “static” files for nearly everything — we only use `.php` files for stuff that requires immediate database interaction like posting, reporting, deleting and of course the entirety of the moderator interface. This is for performance reasons; static files load much faster than `.php` files, which query the database upon every single page load, wasting valuble processing power. Although you can create your own `index.php` to do whatever you like, Tinyboard recommends the use of our themes system which builds `.html` files when an action occurs, such as posting.

APC, XCache and Memcached
-------------------------
On top of the static file caching system, you can enable the additional caching system which is designed to minimize SQL queries and can significantly increase speeds when posting or using the moderator interface.

### APC
[APC (Alternative PHP Cache)](https://web.archive.org/web/20121003095626/http://php.net/manual/en/book.apc.php) is the preferred method of caching. To enable it, add the following to your [`instance-config.php`](../../inc/instance-config.php):
```php
$config['cache']['enabled'] = 'apc';
```
You will need to install the APC extension if you don’t already have it. If you have PECL, you can use
```
pecl install apc
```

### XCache

[XCache](https://web.archive.org/web/20121003095626/http://xcache.lighttpd.net/) seems to be similar to APC but has some improvements.
```php
$config['cache']['enabled'] = 'xcache';
```
You can install Xcache [from source](https://web.archive.org/web/20121003095626/http://xcache.lighttpd.net/wiki/InstallFromSource) or [from binary release](https://web.archive.org/web/20121003095626/http://xcache.lighttpd.net/wiki/InstallFromBinary).

### Memcached

To use Memcached, you have to be running a daemon memcached process at all times.
```php
$config['cache']['enabled'] = 'memcached';

$config['cache']['memcached'] = array(
	array('localhost', 11211)
);
```
Install memcached with
```
pecl install memcached
```
Note: Tinyboard uses **memcached** — not **memcache**; there is a difference.
