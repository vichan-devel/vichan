reCAPTCHA
=========

To enable reCAPTCHA, grab a public and private key pair from [here](https://web.archive.org/web/20121016193132/https://www.google.com/recaptcha/admin/create) and add them to [a config file](../config.md).
```php
$config['recaptcha'] = true;
$config['recaptcha_public'] = '<your public key>';
$config['recaptcha_private']    = '<your private Key>';
```
