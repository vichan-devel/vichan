<?php
require 'inc/bootstrap.php';

if (!isset($_GET['board']) || !preg_match("/{$config['board_regex']}/u", $_GET['board'])) {
	http_response_code(400);
	error('Bad board.');
}
if (!openBoard($_GET['board'])) {
	http_response_code(404);
	error('No board.');
}

if ($config['public_logs'] == 0) error('This board has public logs disabled. Ask the board owner to enable it.');
if ($config['public_logs'] == 1) $hide_names = false;
if ($config['public_logs'] == 2) $hide_names = true;

if (!isset($_GET['page'])) {
	$page = 1;
} else {
	$page = (int)$_GET['page'];
};

mod_board_log($board['uri'], $page, $hide_names, true);
