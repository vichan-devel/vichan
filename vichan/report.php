<?php
require 'inc/bootstrap.php';
$global = isset($_GET['global']);
$post = (isset($_GET['post']) ? $_GET['post'] : false);
$board = (isset($_GET['board']) ? $_GET['board'] : false);

if (!$post || !preg_match('/^delete_\d+$/', $post) || !$board || !openBoard($board)) {
	header('HTTP/1.1 400 Bad Request');
	error(_('Bad request.'));
}

if ($config['report_captcha']) {
	$captcha = generate_captcha($config['captcha']['extra']);
} else {
	$captcha = null;
}

$body = Element($config['file_report'], ['global' => $global, 'post' => $post, 'board' => $board, 'captcha' => $captcha, 'config' => $config]);
echo Element($config['file_page_template'], ['config' => $config, 'body' => $body]);
