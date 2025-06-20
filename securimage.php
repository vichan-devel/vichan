<?php
require_once('inc/bootstrap.php');

function rand_string(int $length, string $charset): string {
	$ret = '';
	while ($length--) {
		$ret .= mb_substr($charset, rand(0, mb_strlen($charset, 'utf-8')-1), 1, 'utf-8');
	}
	return $ret;
}

function cleanup(int $expires_in): void {
	prepare("DELETE FROM `captchas` WHERE `created_at` < ?")->execute([time() - $expires_in]);
}

function handleGetRequestCaptcha(array $config): void {
	$extra = $config['captcha']['native']['extra'];
	$cookie = rand_string(20, $extra);

	$securimage = new Securimage($config['captcha']['native']['securimage_options']);
	$securimage->createCode();

	ob_start();
	$securimage->show();
	$rawImage = ob_get_clean();

	$base64Image = 'data:image/png;base64,' . base64_encode($rawImage);
	$html = '<img src="' . $base64Image . '">';
	$captchaCode = $securimage->getCode();

	prepare("INSERT INTO `captchas` (`cookie`, `extra`, `text`, `created_at`) VALUES (?, ?, ?, ?)")
		->execute([$cookie, $extra, $captchaCode->code_display, $captchaCode->creationTime]);

	if (isset($_GET['raw'])) {
		$_SESSION['captcha_cookie'] = $cookie;
		header('Content-Type: image/png');
		echo $rawImage;
	} else {
		header("Content-Type: application/json");
		echo json_encode([
			"cookie" => $cookie,
			"captchahtml" => $html,
			"expires_in" => $config['captcha']['native']['expires_in'],
		]);
	}
}

function handleCheckRequestCaptcha(int $expires_in): void {
	cleanup($expires_in);

	$cookie = $_GET['cookie'] ?? null;
	$text = $_GET['text'] ?? null;

	if (!$cookie || !$text) {
		echo json_encode(["success" => false]);
		return;
	}

	$query = prepare("SELECT * FROM `captchas` WHERE `cookie` = ?");
	$query->execute([$cookie]);
	$captchaData = $query->fetchAll();

	if (!$captchaData) {
		echo json_encode(["success" => false]);
		return;
	}

	prepare("DELETE FROM `captchas` WHERE `cookie` = ?")->execute([$cookie]);

	$isSuccessful = $captchaData[0]['text'] === $text;
	echo json_encode(["success" => $isSuccessful]);
}

$mode = $_GET['mode'] ?? null;

switch($mode) {
	case 'get':
		handleGetRequestCaptcha($config);
		break;
	case 'check':
		handleCheckRequestCaptcha($config['captcha']['native']['expires_in']);
		break;
	case '':
	default:
		http_response_code(400);
		echo json_encode(["success" => false, "error" => "Invalid mode"]);
		break;
}