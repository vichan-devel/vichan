<?php
include 'inc/functions.php';
$global = isset($_GET['global']);
$post = (isset($_GET['post']) ? $_GET['post'] : false);
$board = (isset($_GET['board']) ? $_GET['board'] : false);

if (!$post || !preg_match('/^delete_\d+$/', $post) || !$board || !openBoard($board)) {
	header('HTTP/1.1 400 Bad Request');
	error(_('Bad request.'));
}

$body = Element('report.html', ['global' => $global, 'post' => $post, 'board' => $board, 'config' => $config]);
echo Element('page.html', ['config' => $config, 'body' => $body]);
