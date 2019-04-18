“Your request looks automated”
==============================

Tinyboard’s “anti-bot” measure is unique to those in other imageboard engines. In simple terms, whenever a posting form on a page is generated (which happens whenever a new post is made), Tinyboard will add a random amount of hidden, obscure inputs to it to confuse bots and upset hackers. These fields and their respective values are validated upon posting with a 160-bit “hash”. A hash can only be used a number of times, and can expire.

There are a total of three scenarios that could be the cause of a “Your request looks automated” error.

### #1. Some browsers do things wrong
The method of adding randomly generated fields to a form is appears to be very successful in stopping generic spambots. However, although the random inputs are fully standards-compliant, some (non-compliant) web browsers occasionally get things wrong and normal users are incorrectly deemed bots.

All major browsers seem to handle the fields without trouble, but there are some (specifically mobile) web browsers that will interpret things incorrectly. If you are having trouble with this error message and think the problem might be your browser, please join our [IRC channel](https://web.archive.org/web/20121003095610/irc://irc.datnode.net/tinyboard) to help us debug it.

### #2. “Hashes” expire
Hashes correspond with the set of random input fields in a posting form. They can be used multiple times; otherwise, having more than one person visit the same page at the same time would mean that only one can reply. Posting forms are regenerated when the pages are, meaning that any post on the board will regenerate the main “new thread” form and that any post in a specific thread will regenerate the form for that thread. Once a form is regenerated, the old hash is still usable but is set an expiration date. If they weren’t set to expire and were instead invalidated immediately, prospective posters on the same page at the same time would have to reload before posting. `$config['spam']['hidden_inputs_expire']` sets the time (in seconds) when a hash will expire after a replacement hash has been generated.

### #3. “Hashes” can only be used a number of times
This means that if, for example, 10 people visited the same page at approximately the same time and `$config['spam']['hidden_inputs_max_pass']` was set to `5`, only five of the 10 would be allowed to post. The others will have to reload the page first.

Confused?
---------
If you still don’t understand any of this page, maybe this will help:
```php
$config['spam']['hidden_inputs_max_pass'] = 2; // a hash may be used only twice
$config['spam']['hidden_inputs_expire'] = 60 * 60 * 2; // expires in 2 hours
```
* User A, B, C and D visit the page at approximately the same time. Some time before, a posting form was generated with the unique hash `123456`.
* User A posts. Tinyboard records that `123456` has been used once. The posting form is regenerated with a new hash (`654321`) and `123456` is set to expire in 2 hours.
* User B posts with the same hash. `123456` is noted as being used twice and is still set to expire
* User C tries to post with the same hash again, but `$config['spam']['hidden_inputs_max_pass']` is too low to allow that. His post is rejected on the grounds that a flood attack could be taking place.
* 2 hours pass and user D tries to post with the same hash (without reloading the page first). His post is rejected because the `123456` hash has expired, as it’s been two hours since the first user posted. (Even if the hash hadn’t expired yet, he’d get the same error as user C.)

Configuring
-----------
If you’re not afraid of bots or if your site is somewhat popular, set `$config['spam']['hidden_inputs_max_pass']` and `$config['spam']['hidden_inputs_expire']` to something higher. Basically, `$config['spam']['hidden_inputs_max_pass']` defines how many people can visit a page at the same time and post, while `$config['spam']['hidden_inputs_expire']` defines the maximum amount of time you can sit on a page before posting.

TL;DR
-----
Try reloading the page before posting.
