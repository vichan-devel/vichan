<?php
require_once('inc/bootstrap.php');
$expires_in = 120;

function rand_string($length, $charset) {
	$ret = "";
	while ($length--) {
		$ret .= mb_substr($charset, rand(0, mb_strlen($charset, 'utf-8')-1), 1, 'utf-8');
	}
	return $ret;
}

function cleanup() {
	global $expires_in;
	prepare("DELETE FROM `captchas` WHERE `created_at` < ?")->execute([time() - $expires_in]);
}


$mode = @$_GET['mode'];
switch ($mode) {
	case 'get':
		if (!isset ($_GET['extra'])) {
			$_GET['extra'] = $config['captcha']['extra'];
		}

		header("Content-type: application/json");
		$extra = $_GET['extra'];
		$cookie = rand_string(20, "abcdefghijklmnopqrstuvwxyz");
		$i = new Securimage(['send_headers' => false, 'no_exit' => true]);
		$i->createCode();
		ob_start();
		$i->show();
		$rawimg = ob_get_contents();
		$b64img = 'data:image/png;base64,'.base64_encode($rawimg);
		$html = '<img src="'.$b64img.'">';
		ob_end_clean();
		$cdata = $i->getCode();
		$query = prepare("INSERT INTO `captchas` (`cookie`, `extra`, `text`, `created_at`) VALUES (?, ?, ?, ?)");
		$query->execute([$cookie, $extra, $cdata->code_display, $cdata->creationTime]);
		if (isset($_GET['raw'])) {
			$_SESSION['captcha_cookie'] = $cookie;
			header('Content-Type: image/png');
			echo $rawimg;
		} else {
			echo json_encode(["cookie" => $cookie, "captchahtml" => $html, "expires_in" => $expires_in]);
		}
		break;
	case 'check':
		cleanup();
		if (!isset ($_GET['mode']) || !isset ($_GET['cookie']) || !isset ($_GET['extra']) || !isset ($_GET['text'])) {
			die();
		}

		$query = prepare("SELECT * FROM `captchas` WHERE `cookie` = ? AND `extra` = ?");
		$query->execute([$_GET['cookie'], $_GET['extra']]);

		$ary = $query->fetchAll();

		if (!$ary) { // captcha expired
			echo "0";
			break;
		} else {
			$query = prepare("DELETE FROM `captchas` WHERE `cookie` = ? AND `extra` = ?");
			$query->execute([$_GET['cookie'], $_GET['extra']]);
		}

		if ($ary[0]['text'] !== $_GET['text']) {
			echo "0";
		} else {
			echo "1";
		}
		break;
}
