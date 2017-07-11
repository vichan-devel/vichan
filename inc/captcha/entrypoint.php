<?php
header('Access-Control-Allow-Origin: *');

$mode = @$_GET['mode'];

chdir('../..'); // for "cool PHP CAPTCHA"'s resourcesPath
include "inc/functions.php"; // general 8chan functions
include "inc/captcha/functions.php";

switch ($mode) {
	// Request: GET entrypoint.php?mode=get&extra=1234567890
	// Response: JSON: cookie => "generatedcookie", captchahtml => "captchahtml", expires_in => 120
	case "get":
		global $config;

		if (!isset($_GET['extra'])) {
			die();
		}

		$extra = $_GET['extra'];
		$nojs = isset($_GET['nojs']);
		$captcha = generate_captcha($extra);

		if ($nojs) {
			header("Content-type: text/html");
			echo "<html><body>You do not have JavaScript enabled. To fill out the CAPTCHA, please copy the ID to the post form in the ID field, and write the answer in the answer field.<br><br>CAPTCHA ID: $cookie<br>CAPTCHA image: $html</body></html>";
		} else {
			header("Content-type: application/json");
			echo json_encode(["cookie" => $captcha['cookie'], "captchahtml" => $captcha['html'], "expires_in" => $config['captcha']['expires_in']]);
		}
		
		break;

	// Request: GET entrypoint.php?mode=check&cookie=generatedcookie&extra=1234567890&text=captcha
	// Response: 0 OR 1
	case "check":
		if (!isset ($_GET['mode'])
		 || !isset ($_GET['cookie'])
		 || !isset ($_GET['extra'])
		 || !isset ($_GET['text'])) {
			die();
		}

		cleanup();

		$query = prepare("SELECT * FROM `captchas` WHERE `cookie` = ? AND `text` = ?");
		$query->execute([$_GET['cookie'], $_GET['text']]);
		$ary = $query->fetchAll();

		if (!$ary) {
			echo "0";
		}
		else {
			$query = prepare("DELETE FROM `captchas` WHERE `cookie` = ? AND `text` = ?");
			$query->execute([$_GET['cookie'], $_GET['text']]);

			echo "1";
		}

		break;
}
