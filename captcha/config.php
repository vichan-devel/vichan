<?php
// We are using a custom path here to connect to the database.
// Why? Performance reasons.

$pdo = new PDO("mysql:dbname=database_name;host=localhost", "databas_user", "databas_password", array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

// Captcha expiration:
$expires_in = 300; // 120 seconds

// Captcha dimensions:
$width = 250;
$height = 80;

// Captcha length:
$length = 5;
