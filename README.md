NPFchan - A lightweight and full featured PHP imageboard.
========================================================

About
------------
This is the a fork of vichan running on [mlpol.net](https://mlpol.net), a free light-weight, fast, highly configurable and user-friendly
imageboard software package. It is written in PHP and has few dependencies.

NPFchan is a fork of [vichan](https://github.com/vichan-devel/vichan) which is a fork of (now defunc'd) [Tinyboard](http://github.com/savetheinternet/Tinyboard),
a great imageboard package, actively building on it and adding a lot of features and other
improvements.

Requirements
------------
1.	PHP >= 5.6
2.	MySQL >= 5.6 (MariaDB Untested)
3.	[mbstring](http://www.php.net/manual/en/mbstring.installation.php) 
4.	[PHP GD](http://www.php.net/manual/en/intro.image.php)
5.	[PHP PDO](http://www.php.net/manual/en/intro.pdo.php)

We try to make sure NPFchan is compatible with all major web servers and
operating systems. NPFchan does not include an Apache ```.htaccess``` file nor does
it need one.

### Recommended
1.	MySQL >= 5.7
2.	ImageMagick (command-line ImageMagick or GraphicsMagick preferred).
3.	[APC (Alternative PHP Cache)](http://php.net/manual/en/book.apc.php),
	[XCache](http://xcache.lighttpd.net/) or
	[Memcached](http://www.php.net/manual/en/intro.memcached.php)

Contributing
------------
You can contribute to NPFchan by:
*	Developing patches/improvements/translations and using GitHub to submit pull requests
*	Providing feedback and suggestions
*	Writing/editing documentation

Installation
-------------
See the [Installation Guide](https://github.com/fallenPineapple/NPFchan/wiki/Installation-Guide)

Please remember to change the administrator account password.

See also: [Configuration Basics](https://web.archive.org/web/20121003095922/http://tinyboard.org/docs/?p=Config).

Upgrade
-------
To upgrade from any version of Tinyboard or vichan or NFPchan:

Either run ```git pull``` to update your files, if you used git, or
backup your ```inc/instance-config.php```, replace all your files in place
(don't remove boards etc.), then put ```inc/instance-config.php``` back and
finally run ```install.php```.

To migrate from a Kusaba X board, use http://github.com/vichan-devel/Tinyboard-Migration

Support
--------

As it stands NPFchan has no public support system.

### vichan support
vichan is still beta software -- there are bound to be bugs. If you find a
bug, please report it.

If you need assistance with installing, configuring, or using vichan, you may
find support from a variety of sources:

*	If you're unsure about how to enable or configure certain features, make
	sure you have read the comments in ```inc/config.php```.
*	Check out an [official vichan board](http://int.vichan.net/devel/).
*	You can join vichan's IRC channel for support
	[irc.6irc.net #vichan-devel](irc://irc.6irc.net/vichan-devel)

### Tinyboard support
vichan is based on a Tinyboard, so both engines have very much in common. These
links may be helpful for you as well: 

*	Tinyboard documentation can be found [here](https://web.archive.org/web/20121016074303/http://tinyboard.org/docs/?p=Main_Page).

CLI tools
-----------------
There are a few command line interface tools, based on Tinyboard-Tools. These need
to be launched from a Unix shell account (SSH, or something). They are located in a ```tools/```
directory.

You actually don't need these tools for your imageboard functioning, they are aimed
at the power users. You won't be able to run these from shared hosting accounts
(i.e. all free web servers).

Oekaki
------
NPFchan makes use of [wPaint](https://github.com/websanova/wPaint) for oekaki.

To enable oekaki, add all the scripts listed in `js/wpaint.js` to your `instance-config.php`.

WebM support
------------
Read `inc/lib/webm/README.md` for information about enabling webm.

NPFchan API
----------
NPFchan provides by default a 4chan-compatible JSON API. For documentation on this, see:
https://github.com/vichan-devel/vichan-API/ .

License
--------
See [LICENSE.md](http://github.com/fallenPineapple/NPFchan/blob/master/LICENSE.md).

