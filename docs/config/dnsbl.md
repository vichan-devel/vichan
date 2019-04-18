DNS Blacklists (DNSBL)
======================

To further prevent spam and abuse, you can use DNS blacklists (DNSBL). A DNSBL is a list of IP addresses published through the Internet Domain Name Service (DNS) either as a zone file that can be used by DNS server software, or as a live DNS zone that can be queried in real-time.

By default, Tinyboard checks all addresses against just one blacklist:
```
tor.dnsbl.sectoor.de
```
This prevents Tor exit nodes from making posts and is recommended as a large majority of abuse comes from Tor because of the strong anonymity associated with it.

You can add more blacklists by [putting them in your configuration](../config.md). You should refer to the blacklist’s documentation to figure out which responses you wish to block.
```php
// Block "127.0.0.2" responses from this blacklist.
$config['dnsbl'][] = array('bl.spamcannibal.org', 2);

// This does exactly the same thing as above:
// $config['dnsbl'][] = array('bl.spamcannibal.org', array(2));
// $config['dnsbl'][] = array('bl.spamcannibal.org', '127.0.0.2');

// Block "127.0.0.8" and "127.0.0.9" responses from this blacklist.
$config['disbl'][] = array('dnsbl.dronebl.org', array(8, 9));

// Determine whether or not to block listed users with an anonymous function (PHP 5.3.0+)
// This is especially relevant if you plan to use Http:BL.
$config['dnsbl'][] = array('<your access key>.%.dnsbl.httpbl.org', function($ip) {
	$octets = explode('.', $ip);

	// days since last activity
	if ($octets[1] > 14)
		return false;

	// "threat score" (http://www.projecthoneypot.org/threat_info.php)
	if ($octets[2] < 5)
		return false;

	return true;
}, 'dnsbl.httpbl.org'); // hide our access key


// Just block anything listed in here (ie. not NXDOMAIN)
$config['dnsbl'][] = 'another.blacklist.net';
```
See [here](https://web.archive.org/web/20121003095945/http://www.dnsbl.info/dnsbl-list.php) for a larger list of blacklists.

This syntax for configuring DNSBL servers was stolen (with permission) from [Frank Usrs](https://web.archive.org/web/20121003095945/mailto:frankusrs@gmail.com).

Exceptions
----------
To combat false positives, administrators can add “exceptions” to these lists, meaning that certain IP addresses will skip the DNSBL check entirely.
```php
$config['dnsbl_exceptions'][] = '8.8.8.8';
$config['dnsbl_exceptions'][] = '55.55.55.55';
```
