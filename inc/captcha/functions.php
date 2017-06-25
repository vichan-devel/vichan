<?php
require_once 'cool-php-captcha-0.3.1/captcha.php';

function generate_captcha($extra = '1234567890') {
	global $config;

	$captcha = new SimpleCaptcha();

	$cookie = rand_string(20, "abcdefghijklmnopqrstuvwxyz");

	ob_start();
	$text = $captcha->CreateImage();
	$image = ob_get_contents();
	ob_end_clean();
	$html = '<image src="data:image/png;base64,'.base64_encode($image).'">';

	$query = prepare("INSERT INTO `captchas` (`cookie`, `extra`, `text`, `created_at`) VALUES (?, ?, ?, ?)");
	$query->execute(                               [$cookie,  $extra,  $text,  time()]);

	return array("cookie" => $cookie, "html" => $html);
}

function rand_string($length, $charset) {
	$ret = "";
	while ($length--) {
		$ret .= mb_substr($charset, rand(0, mb_strlen($charset, 'utf-8')-1), 1, 'utf-8');
	}
	return $ret;
}

function cleanup () {
	global $config;
	prepare("DELETE FROM `captchas` WHERE `created_at` < ?")->execute([time() - $config['captcha']['expires_in']]);
}

