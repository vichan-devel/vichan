Installing Tinyboard
====================

Installation for Tinyboard is simple. You used to have to import the database tables and [create a config file](config.md) yourself, but now Tinyboard does it all for you.

Requirements
------------
1. PHP ≥ 5.2.5
2. [mbstring](https://web.archive.org/web/20121017012357/http://www.php.net/manual/en/mbstring.installation.php) (`--enable-mbstring`)
3. [PHP-GD](https://web.archive.org/web/20121017012357/http://php.net/manual/en/book.image.php)
4. [PHP-PDO](https://web.archive.org/web/20121017012357/http://php.net/manual/en/book.pdo.php) with appropriate [~~driver for your database~~](https://web.archive.org/web/20121017012357/http://www.php.net/manual/en/pdo.drivers.php) (only MySQL is supported at the moment)

We try to make sure Tinyboard is compatible with all major web servers and operating systems. Tinyboard does not include an Apache .htaccess file nor does it need one.

Installing
----------
1. Download and extract Tinyboard to your web directory or get the latest development version with:
```
git clone git://github.com/savetheinternet/Tinyboard.git
```
2. Navigate to [`install.php`](../install.php) in your web browser and follow the prompts.
3. Tinyboard should now be installed. Log in to [`mod.php`](../mod.php) with the default username and password combination: **admin** / **password**.

Please remember to change the administrator account password.
