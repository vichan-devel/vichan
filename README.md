vichan - A lightweight and full featured PHP imageboard.
========================================================

**Vichan has reached END OF LIFE (EOL). See [#543](https://github.com/vichan-devel/vichan/issues/543). It will receive NO FURTHER PATCHES, neither for security or anything else, unless the situation therein described substantially changes. Thank you for your years of interest, but this horse is dead. ~ Fredrick R. Brennan <<copypaste@kittens.ph>>**

As of 29 August 2022, though, it supports PHP8.1.

##### Unapplied patches

Some patches remain unapplied due to their uncertain maintenance burden. You may wish to apply them:

<ul dir="auto">
<li><a class="commit-link" href="https://github.com/vichan-devel/vichan/commit/5d31f3bab70de7f983fd56aa18817ede38d1d4f3"><tt>5d31f3b</tt></a> (<span class="reference"><a class="issue-link js-issue-link" data-error-text="Failed to load title" data-id="1530457642" data-permission-text="Title is private" data-url="https://github.com/vichan-devel/vichan/issues/527" data-hovercard-type="pull_request" data-hovercard-url="/vichan-devel/vichan/pull/527/hovercard" href="https://github.com/vichan-devel/vichan/pull/527">Allow open parentheses before cite: "(&gt;&gt;1"<span class="issue-shorthand">&nbsp;#527</span></a></span>) by <a class="user-mention notranslate" href="https://github.com/discomrade">@discomrade</a></li>
</ul>

They will be collected in `static/unapplied patches` upon rejection.

About
------------
vichan is a free light-weight, fast, highly configurable and user-friendly
imageboard software package. It is written in PHP and has few dependencies.

*Security problems can be reported to the development team: DEVELOPMENT \<AT\> VICHAN \<DOT\> NET.*
	
While there is currently no active development besides fixing security problems, we don't exclude the possibility to refactor the code in order to meet today's standards and continue our work from the point where [@czaks](https://github.com/czaks) retired in 2017.
Before this milestone is achieved though, we strongly urge you to consider other imageboard packages. It is the opinion of the vichan development team that no new vichan imageboards should be deployed at the moment, and other imageboard packages used instead.

For support, feel free to join our [IRC channel](https://webchat.6an.org/?channels=vichan-dev) at irc.6an.org.

Some documentation may be found on our [wiki](https://github.com/vichan-devel/vichan/wiki). (feel free to contribute)

History
------------
vichan is a fork of (now defunc'd) [Tinyboard](http://github.com/savetheinternet/Tinyboard),
a great imageboard package, actively building on it and adding a lot of features and other
improvements.

### Maintainer timeline
1. [@h00j](https://github.com/h00j) (2021 - present)
2. [@ctrlcctrlv](https://github.com/ctrlcctrlv) (2017 - 2021)
3. [@czaks](https://github.com/czaks) (2014 - 2017) (The author of vichan fork)
4. [@savetheinternet](https://github.com/savetheinternet) (2010 - 2014) (The creator of Tinyboard)

Requirements
------------
1.	PHP >= 5.4 (we still try to keep compatibility with php 5.3 as much as possible)
        PHP 7.0 is explicitly supported. PHP 7.2 works as well, but may cause as yet unreported bugs.
2.	MySQL/MariaDB server
3.	[mbstring](http://www.php.net/manual/en/mbstring.installation.php) 
4.	[PHP GD](http://www.php.net/manual/en/intro.image.php)
5.	[PHP PDO](http://www.php.net/manual/en/intro.pdo.php)
6.	A Unix-like OS, preferrably FreeBSD or GNU/Linux

We try to make sure vichan is compatible with all major web servers. vichan does not include an Apache `.htaccess` file nor does it need one.

### Recommended
1.	MySQL/MariaDB server >= 5.5.3
2.	ImageMagick (command-line ImageMagick or GraphicsMagick preferred).
3.	~~[APC (Alternative PHP Cache)](http://php.net/manual/en/book.apc.php)~~,
	[APCu (Alternative PHP Cache)](http://php.net/manual/en/book.apcu.php),
	[XCache](http://xcache.lighttpd.net/),
	[Memcached](http://www.php.net/manual/en/intro.memcached.php) or
	[Redis](https://redis.io/docs/about/)

Contributing
------------
You can contribute to vichan by:
*	Developing patches/improvements/translations and using GitHub to submit pull requests
*	Providing feedback and suggestions
*	Writing/editing documentation

Installation
-------------
1.	Download and extract vichan to your web directory or get the latest
	development version with:

        git clone git://github.com/vichan-devel/vichan.git

2.	run ```composer install``` inside the directory	
3.	Navigate to ```install.php``` in your web browser and follow the
	prompts.
4.	vichan should now be installed. Log in to ```mod.php``` with the
	default username and password combination: **admin / password**.

Please remember to change the administrator account password.

See also: [Configuration Basics](https://github.com/vichan-devel/vichan/wiki/config).

Upgrade
-------
To upgrade from any version of Tinyboard or vichan:

Either run ```git pull``` to update your files, if you used git, or
backup your ```inc/instance-config.php```, replace all your files in place
(don't remove boards etc.), then put ```inc/instance-config.php``` back and
finally run ```install.php```.

To migrate from a Kusaba X board, use http://github.com/vichan-devel/Tinyboard-Migration

Demo
--------
Demo with the most updated version of [Vichan](https://vichan.27chan.org).

1. PHP 8.1
2. MySQL 5.7
3. KeyDB 6.2.1 (Redis)
4. NGINX 1.14.0

Support
--------
vichan is still beta software -- there are bound to be bugs. If you find a
bug, please report it.

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
vichan makes use of [wPaint](https://github.com/websanova/wPaint) for oekaki. After you pull the repository, however, you will need to download wPaint separately using git's `submodule` feature. Use the following commands:

```
git submodule init
git submodule update
```

To enable oekaki, add all the scripts listed in `js/wpaint.js` to your `instance-config.php`.

WebM support
------------
Read `inc/lib/webm/README.md` for information about enabling webm.

vichan API
----------
vichan provides by default a 4chan-compatible JSON API. For documentation on this, see:
https://github.com/vichan-devel/vichan-API/ .

License
--------
See [LICENSE.md](http://github.com/vichan-devel/vichan/blob/master/LICENSE.md).

