Bans
====

Tinyboard currently accepts three forms of bans:
* IP address (“static”) bans
* Range/wildcard bans
* CIDR netmask bans

Static bans apply to a single IP addess, such as “127.0.0.1” and “::1” for IPv6.

IP range bans allow the use of the asterisk character (`*`) as a wildcard. For example, “127.0.0.” or just “127.0.” would match “127.0.0.1”. To use this, `$config['ban_range']` must be enabled.
```php
$config['ban_range'] = true;
```
With CDIR netmask bans, “10.0.0.0/8” would match “10.0.0.0 - 10.255.255.255” for example. Due to MySQL limitations, this is currently an IPv4-only feature. To use this, `$config['ban_cdir']` must be enabled.
```php
$config['ban_cidr'] = true;
```
See also
--------
* [Configuration basics](config.md)
* [Mod interface](mod_interface.md)
