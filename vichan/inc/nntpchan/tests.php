<?php
define('TINYBOARD', 'fuck yeah');
require_once('nntpchan.php');


die();

$time = time();
echo "\n@@@@ Thread:\n";
echo $m0 = gennntp(["From" => "czaks <marcin@6irc.net>", "Message-Id" => "<1234.0000.".$time."@example.vichan.net>", "Newsgroups" => "overchan.test", "Date" => time(), "Subject" => "None"],
[['type' => 'text/plain', 'text' => "THIS IS A NEW TEST THREAD"]]);
echo "\n@@@@ Single msg:\n";
echo $m1 = gennntp(["From" => "czaks <marcin@6irc.net>", "Message-Id" => "<1234.1234.".$time."@example.vichan.net>", "Newsgroups" => "overchan.test", "Date" => time(), "Subject" => "None", "References" => "<1234.0000.".$time."@example.vichan.net>"],
[['type' => 'text/plain', 'text' => "hello world, with no image :("]]);
echo "\n@@@@ Single msg and pseudoimage:\n";
echo $m2 = gennntp(["From" => "czaks <marcin@6irc.net>", "Message-Id" => "<1234.2137.".$time."@example.vichan.net>", "Newsgroups" => "overchan.test", "Date" => time(), "Subject" => "None", "References" => "<1234.0000.".$time."@example.vichan.net>"],
[['type' => 'text/plain', 'text' => "hello world, now with an image!"],
 ['type' => 'image/gif', 'text' => base64_decode("R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs="), 'name' => "urgif.gif"]]);
echo "\n@@@@ Single msg and two pseudoimages:\n";
echo $m3 = gennntp(["From" => "czaks <marcin@6irc.net>", "Message-Id" => "<1234.1488.".$time."@example.vichan.net>", "Newsgroups" => "overchan.test", "Date" => time(), "Subject" => "None", "References" => "<1234.0000.".$time."@example.vichan.net>"],
[['type' => 'text/plain', 'text' => "hello world, now WITH TWO IMAGES!!!"],
 ['type' => 'image/gif', 'text' => base64_decode("R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs="), 'name' => "urgif.gif"],
 ['type' => 'image/gif', 'text' => base64_decode("R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs="), 'name' => "urgif2.gif"]]);
shoveitup($m0, "<1234.0000.".$time."@example.vichan.net>");
sleep(1);
shoveitup($m1, "<1234.1234.".$time."@example.vichan.net>");
sleep(1);
shoveitup($m2, "<1234.2137.".$time."@example.vichan.net>");
shoveitup($m3, "<1234.1488.".$time."@example.vichan.net>");

