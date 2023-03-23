<?php
// We are using a custom path here to connect to the database.
// Why? Performance reasons.

$pdo = new PDO("mysql:dbname=database_name;host=localhost", "database_user", "database_password", array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));


// Captcha expiration:
$expires_in = 120; // 120 seconds

// Captcha dimensions:
$width = 250;
$height = 80;

// Captcha length:
$length = 6;
