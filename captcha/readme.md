I integrated this from: https://github.com/ctrlcctrlv/infinity/commit/62a6dac022cb338f7b719d0c35a64ab3efc64658

First import the captcha/dbschema.sql in your database
in captcha/config.phpchange the database_name database_user database_password to your own settings.
Go to Line 305 in the /inc/config file and copy the settings in instance config. 
Go to line 461 if you only want to enable it when posting a new thread.
