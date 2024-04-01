<?php
/*
 *  Copyright (c) 2010-2014 Tinyboard Development Group
 */

require_once 'inc/bootstrap.php';

/**
 * Utility functions
 */

/**
 * Get the md5 hash of the file.
 *
 * @param array $config instance configuration.
 * @param string $file file to the the md5 of.
 * @return string|false
 */
function md5_hash_of_file($config, $path) {
	$cmd = false;
	if ($config['bsd_md5']) {
		$cmd = '/sbin/md5 -r';
	}
	if ($config['gnu_md5']) {
		$cmd = 'md5sum';
	}

	if ($cmd) {
		$output = shell_exec_error($cmd . " " . escapeshellarg($path));
		$output = explode(' ', $output);
		return $output[0];
	} else {
		return md5_file($path);
	}
}

/**
 * Strip the symbols incompatible with the current database version.
 *
 * @param string @input The input string.
 * @return string The value stripped of incompatible symbols.
 */
function strip_symbols($input) {
	if (mysql_version() >= 50503) {
		return $input; // Assume we're using the utf8mb4 charset
	} else {
		// MySQL's `utf8` charset only supports up to 3-byte symbols
		// Remove anything >= 0x010000

		$chars = preg_split('//u', $input, -1, PREG_SPLIT_NO_EMPTY);
		$ret = '';
		foreach ($chars as $char) {
			$o = 0;
			$ord = ordutf8($char, $o);
			if ($ord >= 0x010000) {
				continue;
			}
			$ret .= $char;
		}
		return $ret;
	}
}

/**
 * Download the url's target with curl.
 *
 * @param string $url Url to the file to download.
 * @param int $timeout Request timeout in seconds.
 * @param File $fd File descriptor to save the content to.
 * @return null|string Returns a string on error.
 */
function download_file_into($url, $timeout, $fd) {
	$err = null;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Tinyboard');
	curl_setopt($curl, CURLOPT_FILE, $fd);
	curl_setopt($curl, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
	curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

	if (curl_exec($curl) === false) {
		$err = curl_error($curl);
	}

	curl_close($curl);
	return $err;
}

/**
 * Download a remote file from the given url.
 * The file is deleted at shutdown.
 *
 * @param string $file_url The url to download the file from.
 * @param int $request_timeout Timeout to retrieve the file.
 * @param array $extra_extensions Allowed file extensions.
 * @param string $tmp_dir Temporary directory to save the file into.
 * @param array $error_array An array with error codes, used to create exceptions on failure.
 * @return array Returns an array describing the file on success.
 * @throws Exception on error.
 */
function download_file_from_url($file_url, $request_timeout, $allowed_extensions, $tmp_dir, &$error_array) {
	if (!preg_match('@^https?://@', $file_url)) {
		throw new InvalidArgumentException($error_array['invalidimg']);
	}

	if (mb_strpos($file_url, '?') !== false) {
		$url_without_params = mb_substr($file_url, 0, mb_strpos($file_url, '?'));
	} else {
		$url_without_params = $file_url;
	}

	$extension = strtolower(mb_substr($url_without_params, mb_strrpos($url_without_params, '.') + 1));

	if (!in_array($extension, $allowed_extensions)) {
		throw new InvalidArgumentException($error_array['unknownext']);
	}

	$tmp_file = tempnam($tmp_dir, 'url');
	function unlink_tmp_file($file) {
		@unlink($file);
		fatal_error_handler();
	}
	register_shutdown_function('unlink_tmp_file', $tmp_file);

	$fd = fopen($tmp_file, 'w');

	$dl_err = download_file_into($fd, $request_timeout, $fd);
	fclose($fd);
	if ($dl_err !== null) {
		throw new Exception($error_array['nomove'] . '<br/>Curl says: ' . $dl_err);
	}

	return array(
		'name' => basename($url_without_params),
		'tmp_name' => $tmp_file,
		'file_tmp' => true,
		'error' => 0,
		'size' => filesize($tmp_file)
	);
}

/**
 * Try extract text from the given image.
 *
 * @param array $config Instance configuration.
 * @param string $img_path The file path to the image.
 * @return string|false Returns a string with the extracted text on success (if any).
 * @throws RuntimeException Throws if executing tesseract fails.
 */
function ocr_image(array $config, string $img_path): string {
	// The default preprocess command is an ImageMagick b/w quantization.
	$ret = shell_exec_error(
		sprintf($config['tesseract_preprocess_command'], escapeshellarg($img_path))
		 . ' | tesseract stdin stdout 2>/dev/null'
		 . $config['tesseract_params']
	);
	if ($ret === false) {
		throw new RuntimeException('Unable to run tesseract');
	}

	return trim($ret);
}

/**
 * Trim an image's EXIF metadata
 *
 * @param string $img_path The file path to the image.
 * @return int The size of the stripped file.
 * @throws RuntimeException Throws on IO errors.
 */
function strip_image_metadata(string $img_path): int {
	$err = shell_exec_error('exiftool -overwrite_original -ignoreMinorErrors -q -q -all= ' . escapeshellarg($img_path));
	if ($err === false) {
		throw new RuntimeException('Could not strip EXIF metadata!');
	}
	clearstatcache(true, $img_path);
	$ret = filesize($img_path);
	if ($ret === false) {
		throw new RuntimeException('Could not calculate file size!');
	}
	return $ret;
}

/**
 * Method handling functions
 */

$dropped_post = false;

// Is it a post coming from NNTP? Let's extract it and pretend it's a normal post.
if (isset($_GET['Newsgroups']) && $config['nntpchan']['enabled']) {
	if ($_SERVER['REMOTE_ADDR'] != $config['nntpchan']['trusted_peer']) {
		error("NNTPChan: Forbidden. $_SERVER[REMOTE_ADDR] is not a trusted peer");
	}

	$_POST = array();
	$_POST['json_response'] = true;

	$headers = json_encode($_GET);

	if (!isset ($_GET['Message-Id'])) {
		if (!isset ($_GET['Message-ID'])) {
			error("NNTPChan: No message ID");
		}
		else $msgid = $_GET['Message-ID'];
	}
	else $msgid = $_GET['Message-Id'];

	$groups = preg_split("/,\s*/", $_GET['Newsgroups']);
	if (count($groups) != 1) {
		error("NNTPChan: Messages can go to only one newsgroup");
	}
	$group = $groups[0];

	if (!isset($config['nntpchan']['dispatch'][$group])) {
		error("NNTPChan: We don't synchronize $group");
	}
	$xboard = $config['nntpchan']['dispatch'][$group];

	$ref = null;
	if (isset ($_GET['References'])) {
		$refs = preg_split("/,\s*/", $_GET['References']);

		if (count($refs) > 1) {
			error("NNTPChan: We don't support multiple references");
		}

		$ref = $refs[0];

		$query = prepare("SELECT `board`,`id` FROM ``nntp_references`` WHERE `message_id` = :ref");
		$query->bindValue(':ref', $ref);
		$query->execute() or error(db_error($query));

		$ary = $query->fetchAll(PDO::FETCH_ASSOC);

		if (count($ary) == 0) {
			error("NNTPChan: We don't have $ref that $msgid references");
		}

		$p_id = $ary[0]['id'];
		$p_board = $ary[0]['board'];

		if ($p_board != $xboard) {
			error("NNTPChan: Cross board references not allowed. Tried to reference $p_board on $xboard");
		}

		$_POST['thread'] = $p_id;
	}

	$date = isset($_GET['Date']) ? strtotime($_GET['Date']) : time();

	list($ct) = explode('; ', $_GET['Content-Type']);

	$query = prepare("SELECT COUNT(*) AS `c` FROM ``nntp_references`` WHERE `message_id` = :msgid");
	$query->bindValue(":msgid", $msgid);
	$query->execute() or error(db_error($query));

	$a = $query->fetch(PDO::FETCH_ASSOC);
	if ($a['c'] > 0) {
		error("NNTPChan: We already have this post. Post discarded.");
	}

	if ($ct == 'text/plain') {
		$content = file_get_contents("php://input");
	}
	elseif ($ct == 'multipart/mixed' || $ct == 'multipart/form-data') {
		_syslog(LOG_INFO, "MM: Files: ".print_r($GLOBALS, true)); // Debug

		$content = '';

		$newfiles = array();
		foreach ($_FILES['attachment']['error'] as $id => $error) {
			if ($_FILES['attachment']['type'][$id] == 'text/plain') {
				$content .= file_get_contents($_FILES['attachment']['tmp_name'][$id]);
			}
			elseif ($_FILES['attachment']['type'][$id] == 'message/rfc822') { // Signed message, ignore for now
			}
			else { // A real attachment :^)
				$file = array();
				$file['name']     = $_FILES['attachment']['name'][$id];
				$file['type']     = $_FILES['attachment']['type'][$id];
				$file['size']     = $_FILES['attachment']['size'][$id];
				$file['tmp_name'] = $_FILES['attachment']['tmp_name'][$id];
				$file['error']    = $_FILES['attachment']['error'][$id];

				$newfiles["file$id"] = $file;
			}
		}

		$_FILES = $newfiles;
	}
	else {
		error("NNTPChan: Wrong mime type: $ct");
	}

	$_POST['subject'] = isset($_GET['Subject']) ? ($_GET['Subject'] == 'None' ? '' : $_GET['Subject']) : '';
	$_POST['board'] = $xboard;

	if (isset ($_GET['From'])) {
		list($name, $mail) = explode(" <", $_GET['From'], 2);
		$mail = preg_replace('/>\s+$/', '', $mail);

		$_POST['name'] = $name;
		//$_POST['email'] = $mail;
		$_POST['email'] = '';
	}

	if (isset ($_GET['X_Sage'])) {
		$_POST['email'] = 'sage';
	}

	$content = preg_replace_callback('/>>([0-9a-fA-F]{6,})/', function($id) use ($xboard) {
		$id = $id[1];

		$query = prepare("SELECT `board`,`id` FROM ``nntp_references`` WHERE `message_id_digest` LIKE :rule");
		$idx = $id . "%";
		$query->bindValue(':rule', $idx);
		$query->execute() or error(db_error($query));

		$ary = $query->fetchAll(PDO::FETCH_ASSOC);
		if (count($ary) == 0) {
			return ">>>>$id";
		}
		else {
			$ret = array();
			foreach ($ary as $v) {
				if ($v['board'] != $xboard) {
					$ret[] = ">>>/".$v['board']."/".$v['id'];
				}
				else {
					$ret[] = ">>".$v['id'];
				}
			}
			return implode($ret, ", ");
		}
	}, $content);

	$_POST['body'] = $content;

	$dropped_post = array(
		'date' => $date,
		'board' => $xboard,
		'msgid' => $msgid,
		'headers' => $headers,
		'from_nntp' => true,
	);
}
elseif (isset($_GET['Newsgroups'])) {
	error("NNTPChan: NNTPChan support is disabled");
}

session_start();
if (!isset($_POST['captcha_cookie']) && isset($_SESSION['captcha_cookie'])) {
	$_POST['captcha_cookie'] = $_SESSION['captcha_cookie'];
}

if (isset($_POST['delete'])) {
	// Delete

	if (!isset($_POST['board'], $_POST['password']))
		error($config['error']['bot']);

	$password = &$_POST['password'];

	if ($password == '')
		error($config['error']['invalidpassword']);

	$delete = array();
	foreach ($_POST as $post => $value) {
		if (preg_match('/^delete_(\d+)$/', $post, $m)) {
			$delete[] = (int)$m[1];
		}
	}

	checkDNSBL();

	// Check if board exists
	if (!openBoard($_POST['board']))
		error($config['error']['noboard']);

	if ((!isset($_POST['mod']) || !$_POST['mod']) && $config['board_locked']) {
		error("Board is locked");
	}

	// Check if banned
	checkBan($board['uri']);

	// Check if deletion enabled
	if (!$config['allow_delete'])
		error(_('Post deletion is not allowed!'));

	if (empty($delete))
		error($config['error']['nodelete']);

	foreach ($delete as &$id) {
		$query = prepare(sprintf("SELECT `id`,`thread`,`time`,`password` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
		$query->bindValue(':id', $id, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));

		if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			$thread = false;
			if ($config['user_moderation'] && $post['thread']) {
				$thread_query = prepare(sprintf("SELECT `time`,`password` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
				$thread_query->bindValue(':id', $post['thread'], PDO::PARAM_INT);
				$thread_query->execute() or error(db_error($query));

				$thread = $thread_query->fetch(PDO::FETCH_ASSOC);
			}

			if ($post['time'] < time() - $config['max_delete_time'] && $config['max_delete_time'] != false) {
				error(sprintf($config['error']['delete_too_late'], until($post['time'] + $config['max_delete_time'])));
			}

			if ($password != '' && $post['password'] != $password && (!$thread || $thread['password'] != $password))
				error($config['error']['invalidpassword']);

			if ($post['time'] > time() - $config['delete_time'] && (!$thread || $thread['password'] != $password)) {
				error(sprintf($config['error']['delete_too_soon'], until($post['time'] + $config['delete_time'])));
			}

			$ip = $_SERVER['REMOTE_ADDR'];
			if (isset($_POST['file'])) {
				// Delete just the file
				deleteFile($id);
				modLog("User at $ip deleted file from their own post #$id");
			} else {
				// Delete entire post
				deletePost($id);
				modLog("User at $ip deleted their own post #$id");
			}

			_syslog(LOG_INFO, 'Deleted post: ' .
				'/' . $board['dir'] . $config['dir']['res'] . link_for($post) . ($post['thread'] ? '#' . $id : '')
			);
		}
	}

	buildIndex();

	$is_mod = isset($_POST['mod']) && $_POST['mod'];
	$root = $is_mod ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];

	if (!isset($_POST['json_response'])) {
		header('Location: ' . $root . $board['dir'] . $config['file_index'], true, $config['redirect_http']);
	} else {
		header('Content-Type: text/json');
		echo json_encode(array('success' => true));
	}

	// We are already done, let's continue our heavy-lifting work in the background (if we run off FastCGI)
	if (function_exists('fastcgi_finish_request'))
		@fastcgi_finish_request();

	rebuildThemes('post-delete', $board['uri']);

} elseif (isset($_POST['report'])) {
	if (!isset($_POST['board'], $_POST['reason']))
		error($config['error']['bot']);

	$report = array();
	foreach ($_POST as $post => $value) {
		if (preg_match('/^delete_(\d+)$/', $post, $m)) {
			$report[] = (int)$m[1];
		}
	}

	checkDNSBL();

	// Check if board exists
	if (!openBoard($_POST['board']))
		error($config['error']['noboard']);

	if ((!isset($_POST['mod']) || !$_POST['mod']) && $config['board_locked']) {
		error("Board is locked");
	}

	// Check if banned
	checkBan($board['uri']);

	if (empty($report))
		error($config['error']['noreport']);

	if (count($report) > $config['report_limit'])
		error($config['error']['toomanyreports']);

	if ($config['report_captcha'] && !isset($_POST['captcha_text'], $_POST['captcha_cookie'])) {
		error($config['error']['bot']);
	}

	if ($config['report_captcha']) {
		$ch = curl_init($config['domain'].'/'.$config['captcha']['provider_check'] . "?" . http_build_query([
			'mode' => 'check',
			'text' => $_POST['captcha_text'],
			'extra' => $config['captcha']['extra'],
			'cookie' => $_POST['captcha_cookie']
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$resp = curl_exec($ch);

		if ($resp !== '1') {
			error($config['error']['captcha']);
		}
	}

	$reason = escape_markup_modifiers($_POST['reason']);
	markup($reason);

	if (mb_strlen($reason) > $config['report_max_length']) {
		error($config['error']['toolongreport']);
	}

	foreach ($report as &$id) {
		$query = prepare(sprintf("SELECT `id`, `thread` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
		$query->bindValue(':id', $id, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));

		$post = $query->fetch(PDO::FETCH_ASSOC);
		if ($post === false) {
			if ($config['syslog']) {
				_syslog(LOG_INFO, "Failed to report non-existing post #{$id} in {$board['dir']}");
			}
			error($config['error']['nopost']);
		}

		$error = event('report', array('ip' => $_SERVER['REMOTE_ADDR'], 'board' => $board['uri'], 'post' => $post, 'reason' => $reason, 'link' => link_for($post)));
		if ($error) {
			error($error);
		}

		if ($config['syslog'])
			_syslog(LOG_INFO, 'Reported post: ' .
				'/' . $board['dir'] . $config['dir']['res'] . link_for($post) . ($post['thread'] ? '#' . $id : '') .
				' for "' . $reason . '"'
			);
		$query = prepare("INSERT INTO ``reports`` VALUES (NULL, :time, :ip, :board, :post, :reason)");
		$query->bindValue(':time', time(), PDO::PARAM_INT);
		$query->bindValue(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
		$query->bindValue(':board', $board['uri'], PDO::PARAM_STR);
		$query->bindValue(':post', $id, PDO::PARAM_INT);
		$query->bindValue(':reason', $reason, PDO::PARAM_STR);
		$query->execute() or error(db_error($query));
	}

	$is_mod = isset($_POST['mod']) && $_POST['mod'];
	$root = $is_mod ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];

	if (!isset($_POST['json_response'])) {
		$index = $root . $board['dir'] . $config['file_index'];
		echo Element($config['file_page_template'], array('config' => $config, 'body' => '<div style="text-align:center"><a href="javascript:window.close()">[ ' . _('Close window') ." ]</a> <a href='$index'>[ " . _('Return') . ' ]</a></div>', 'title' => _('Report submitted!')));
	} else {
		header('Content-Type: text/json');
		echo json_encode(array('success' => true));
	}
} elseif (isset($_POST['post']) || $dropped_post) {
	if (!isset($_POST['body'], $_POST['board']) && !$dropped_post)
		error($config['error']['bot']);

	$post = array('board' => $_POST['board'], 'files' => array());

	// Check if board exists
	if (!openBoard($post['board']))
		error($config['error']['noboard']);

	if ((!isset($_POST['mod']) || !$_POST['mod']) && $config['board_locked']) {
		error("Board is locked");
	}

	if (!isset($_POST['name']))
		$_POST['name'] = $config['anonymous'];

	if (!isset($_POST['email']))
		$_POST['email'] = '';

	if (!isset($_POST['subject']))
		$_POST['subject'] = '';

	if (!isset($_POST['password']))
		$_POST['password'] = '';

	if (isset($_POST['thread'])) {
		$post['op'] = false;
		$post['thread'] = round($_POST['thread']);
	} else
		$post['op'] = true;


	if (!$dropped_post) {
		if ($config['simple_spam'] && $post['op']) {
			if (!isset($_POST['simple_spam']) || strtolower($config['simple_spam']['answer']) != strtolower($_POST['simple_spam'])) {
				error($config['error']['simple_spam']);
			}
		}

		// Check if banned
		checkBan($board['uri']);

		// Check for CAPTCHA right after opening the board so the "return" link is in there
		if ($config['recaptcha']) {
			if (!isset($_POST['g-recaptcha-response']))
				error($config['error']['bot']);

			// Check what reCAPTCHA has to say...
			$resp = json_decode(file_get_contents(sprintf('https://www.recaptcha.net/recaptcha/api/siteverify?secret=%s&response=%s&remoteip=%s',
				$config['recaptcha_private'],
				urlencode($_POST['g-recaptcha-response']),
				$_SERVER['REMOTE_ADDR'])), true);

			if (!$resp['success']) {
				error($config['error']['captcha']);
			}
		}
		// hCaptcha
		if ($config['hcaptcha']) {
			if (!isset($_POST['h-captcha-response'])) {
				error($config['error']['bot']);
			}

			$data = array(
				'secret' => $config['hcaptcha_private'],
				'response' => $_POST['h-captcha-response'],
				'remoteip' => $_SERVER['REMOTE_ADDR']
			);

			$hcaptchaverify = curl_init();
			curl_setopt($hcaptchaverify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
			curl_setopt($hcaptchaverify, CURLOPT_POST, true);
			curl_setopt($hcaptchaverify, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($hcaptchaverify, CURLOPT_RETURNTRANSFER, true);
			$hcaptcharesponse = curl_exec($hcaptchaverify);

			$resp = json_decode($hcaptcharesponse, true); // Decoding $hcaptcharesponse instead of $response

			if (!$resp['success']) {
				error($config['error']['captcha']);
			}
		}
		// Same, but now with our custom captcha provider
 		if (($config['captcha']['enabled']) || (($post['op']) && ($config['new_thread_capt'])) ) {
		$ch = curl_init($config['domain'].'/'.$config['captcha']['provider_check'] . "?" . http_build_query([
			'mode' => 'check',
			'text' => $_POST['captcha_text'],
			'extra' => $config['captcha']['extra'],
			'cookie' => $_POST['captcha_cookie']
		]));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$resp = curl_exec($ch);

		if ($resp !== '1') {
			error($config['error']['captcha'] .
				'<script>if (actually_load_captcha !== undefined) actually_load_captcha("'.$config['captcha']['provider_get'].'", "'.$config['captcha']['extra'].'");</script>');
		}
	}

		if (!(($post['op'] && $_POST['post'] == $config['button_newtopic']) ||
			(!$post['op'] && $_POST['post'] == $config['button_reply'])))
			error($config['error']['bot']);

		// Check the referrer
		if ($config['referer_match'] !== false &&
			(!isset($_SERVER['HTTP_REFERER']) || !preg_match($config['referer_match'], rawurldecode($_SERVER['HTTP_REFERER']))))
			error($config['error']['referer']);

		checkDNSBL();


		if ($post['mod'] = isset($_POST['mod']) && $_POST['mod']) {
			check_login(false);
			if (!$mod) {
				// Liar. You're not a mod.
				error($config['error']['notamod']);
			}

			$post['sticky'] = $post['op'] && isset($_POST['sticky']);
			$post['locked'] = $post['op'] && isset($_POST['lock']);
			$post['raw'] = isset($_POST['raw']);

			if ($post['sticky'] && !hasPermission($config['mod']['sticky'], $board['uri']))
				error($config['error']['noaccess']);
			if ($post['locked'] && !hasPermission($config['mod']['lock'], $board['uri']))
				error($config['error']['noaccess']);
			if ($post['raw'] && !hasPermission($config['mod']['rawhtml'], $board['uri']))
				error($config['error']['noaccess']);
		}

		if (!$post['mod']) {
			$post['antispam_hash'] = checkSpam(array($board['uri'], isset($post['thread']) ? $post['thread'] : ($config['try_smarter'] && isset($_POST['page']) ? 0 - (int)$_POST['page'] : null)));
			if ($post['antispam_hash'] === true)
				error($config['error']['spam']);
		}

		if ($config['robot_enable'] && $config['robot_mute']) {
			checkMute();
		}
	}
	else {
		$mod = $post['mod'] = false;
	}

	//Check if thread exists
	if (!$post['op']) {
		$query = prepare(sprintf("SELECT `sticky`,`locked`,`cycle`,`sage`,`slug` FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
		$query->bindValue(':id', $post['thread'], PDO::PARAM_INT);
		$query->execute() or error(db_error());

		if (!$thread = $query->fetch(PDO::FETCH_ASSOC)) {
			// Non-existant
			error($config['error']['nonexistant']);
		}
	}
	else {
		$thread = false;
	}


	// Check for an embed field
	if ($config['enable_embedding'] && isset($_POST['embed']) && !empty($_POST['embed'])) {
		// yep; validate it
		$value = $_POST['embed'];
		foreach ($config['embedding'] as &$embed) {
			if (preg_match($embed[0], $value)) {
				// Valid link
				$post['embed'] = $value;
				// This is bad, lol.
				$post['no_longer_require_an_image_for_op'] = true;
				break;
			}
		}
		if (!isset($post['embed'])) {
			error($config['error']['invalid_embed']);
		}
	}

	if (!hasPermission($config['mod']['bypass_field_disable'], $board['uri'])) {
		if ($config['field_disable_name'])
			$_POST['name'] = $config['anonymous']; // "forced anonymous"

		if ($config['field_disable_email'])
			$_POST['email'] = '';

		if ($config['field_disable_password'])
			$_POST['password'] = '';

		if ($config['field_disable_subject'] || (!$post['op'] && $config['field_disable_reply_subject']))
			$_POST['subject'] = '';
	}

	if ($config['allow_upload_by_url'] && isset($_POST['file_url']) && !empty($_POST['file_url'])) {
		$allowed_extensions = $config['allowed_ext_files'];

		// Add allowed extensions for OP, if enabled.
		if ($post['op'] && $config['allowed_ext_op']) {
			array_merge($allowed_extensions, $config['allowed_ext_op']);
		}

		try {
			$_FILES['file'] = download_file_from_url($_POST['file_url'], $config['upload_by_url_timeout'], $allowed_extensions, $config['tmp'], $config['error']);
		} catch (Exception $e) {
			error($e->getMessage());
		}
	}

	$post['name'] = $_POST['name'] != '' ? $_POST['name'] : $config['anonymous'];
	$post['subject'] = $_POST['subject'];
	$post['email'] = str_replace(' ', '%20', htmlspecialchars($_POST['email']));
	$post['body'] = $_POST['body'];
	$post['password'] = $_POST['password'];
	$post['has_file'] = (!isset($post['embed']) && (($post['op'] && !isset($post['no_longer_require_an_image_for_op']) && $config['force_image_op']) || count($_FILES) > 0));

	if (!$dropped_post) {

		if (!($post['has_file'] || isset($post['embed'])) || (($post['op'] && $config['force_body_op']) || (!$post['op'] && $config['force_body']))) {
			$stripped_whitespace = preg_replace('/[\s]/u', '', $post['body']);
			if ($stripped_whitespace == '') {
				error($config['error']['tooshort_body']);
			}
		}

		if (!$post['op']) {
			// Check if thread is locked
			// but allow mods to post
			if ($thread['locked'] && !hasPermission($config['mod']['postinlocked'], $board['uri']))
				error($config['error']['locked']);

			$numposts = numPosts($post['thread']);

			if ($config['reply_hard_limit'] != 0 && $config['reply_hard_limit'] <= $numposts['replies'])
				error($config['error']['reply_hard_limit']);

			if ($post['has_file'] && $config['image_hard_limit'] != 0 && $config['image_hard_limit'] <= $numposts['images'])
				error($config['error']['image_hard_limit']);
		}
	}
	else {
		if (!$post['op']) {
			$numposts = numPosts($post['thread']);
		}
	}

	if ($post['has_file']) {
		// Determine size sanity
		$size = 0;
		if ($config['multiimage_method'] == 'split') {
			foreach ($_FILES as $key => $file) {
				$size += $file['size'];
			}
		} elseif ($config['multiimage_method'] == 'each') {
			foreach ($_FILES as $key => $file) {
				if ($file['size'] > $size) {
					$size = $file['size'];
				}
			}
		} else {
			error(_('Unrecognized file size determination method.'));
		}

		if ($size > $config['max_filesize'])
			error(sprintf3($config['error']['filesize'], array(
				'sz' => number_format($size),
				'filesz' => number_format($size),
				'maxsz' => number_format($config['max_filesize'])
			)));
		$post['filesize'] = $size;
	}


	$post['capcode'] = false;

	if ($mod && preg_match('/^((.+) )?## (.+)$/', $post['name'], $matches)) {
		$name = $matches[2] != '' ? $matches[2] : $config['anonymous'];
		$cap = $matches[3];

		if (isset($config['mod']['capcode'][$mod['type']])) {
			if (	$config['mod']['capcode'][$mod['type']] === true ||
				(is_array($config['mod']['capcode'][$mod['type']]) &&
					in_array($cap, $config['mod']['capcode'][$mod['type']])
				)) {

				$post['capcode'] = utf8tohtml($cap);
				$post['name'] = $name;
			}
		}
	}

	$trip = generate_tripcode($post['name']);
	$post['name'] = $trip[0];
	$post['trip'] = isset($trip[1]) ? $trip[1] : ''; // XX: Dropped posts and tripcodes

	$noko = false;
	if (strtolower($post['email']) == 'noko') {
		$noko = true;
		$post['email'] = '';
	} elseif (strtolower($post['email']) == 'nonoko'){
		$noko = false;
		$post['email'] = '';
	} else $noko = $config['always_noko'];

	if ($post['has_file']) {
		$i = 0;
		foreach ($_FILES as $key => $file) {
			if (!in_array($file['error'], array(UPLOAD_ERR_NO_FILE, UPLOAD_ERR_OK))) {
				error(sprintf3($config['error']['phpfileserror'], array(
					'index' => $i+1,
					'code' => $file['error']
				)));
			}

			if ($file['size'] && $file['tmp_name']) {
				$file['filename'] = urldecode($file['name']);
				$file['extension'] = strtolower(mb_substr($file['filename'], mb_strrpos($file['filename'], '.') + 1));
				if (isset($config['filename_func']))
					$file['file_id'] = $config['filename_func']($file);
				else
					$file['file_id'] = time() . substr(microtime(), 2, 3);

				if (sizeof($_FILES) > 1)
					$file['file_id'] .= "-$i";

				$file['file'] = $board['dir'] . $config['dir']['img'] . $file['file_id'] . '.' . $file['extension'];
				$file['thumb'] = $board['dir'] . $config['dir']['thumb'] . $file['file_id'] . '.' . ($config['thumb_ext'] ? $config['thumb_ext'] : $file['extension']);
				$post['files'][] = $file;
				$i++;
			}
		}
	}

	if (empty($post['files'])) $post['has_file'] = false;

	if (!$dropped_post) {
		// Check for a file
		if ($post['op'] && !isset($post['no_longer_require_an_image_for_op'])) {
			if (!$post['has_file'] && $config['force_image_op'])
				error($config['error']['noimage']);
		}

		// Check for too many files
		if (sizeof($post['files']) > $config['max_images'])
			error($config['error']['toomanyimages']);
	}

	if ($config['strip_combining_chars']) {
		$post['name'] = strip_combining_chars($post['name']);
		$post['email'] = strip_combining_chars($post['email']);
		$post['subject'] = strip_combining_chars($post['subject']);
		$post['body'] = strip_combining_chars($post['body']);
	}

	if (!$dropped_post) {
		// Check string lengths
		if (mb_strlen($post['name']) > 35)
			error(sprintf($config['error']['toolong'], 'name'));
		if (mb_strlen($post['email']) > 40)
			error(sprintf($config['error']['toolong'], 'email'));
		if (mb_strlen($post['subject']) > 100)
			error(sprintf($config['error']['toolong'], 'subject'));
		if (!$mod && mb_strlen($post['body']) > $config['max_body'])
			error($config['error']['toolong_body']);
		if (!$mod && substr_count($post['body'], "\n") >= $config['maximum_lines'])
			error($config['error']['toomanylines']);
		if (mb_strlen($post['password']) > 20)
			error(sprintf($config['error']['toolong'], 'password'));
	}
	wordfilters($post['body']);

	$post['body'] = escape_markup_modifiers($post['body']);

	if ($mod && isset($post['raw']) && $post['raw']) {
		$post['body'] .= "\n<tinyboard raw html>1</tinyboard>";
	}

	if (!$dropped_post)
	if (($config['country_flags'] && !$config['allow_no_country']) || ($config['country_flags'] && $config['allow_no_country'] && !isset($_POST['no_country']))) {
		$gi=geoip_open('inc/lib/geoip/GeoIPv6.dat', GEOIP_STANDARD);

		function ipv4to6($ip) {
			if (strpos($ip, ':') !== false) {
				if (strpos($ip, '.') > 0)
					$ip = substr($ip, strrpos($ip, ':')+1);
				else return $ip;  //native ipv6
			}
			$iparr = array_pad(explode('.', $ip), 4, 0);
			$part7 = base_convert(($iparr[0] * 256) + $iparr[1], 10, 16);
			$part8 = base_convert(($iparr[2] * 256) + $iparr[3], 10, 16);
			return '::ffff:'.$part7.':'.$part8;
		}

		if ($country_code = geoip_country_code_by_addr_v6($gi, ipv4to6($_SERVER['REMOTE_ADDR']))) {
			if (!in_array(strtolower($country_code), array('eu', 'ap', 'o1', 'a1', 'a2')))
				$post['body'] .= "\n<tinyboard flag>".strtolower($country_code)."</tinyboard>".
				"\n<tinyboard flag alt>".geoip_country_name_by_addr_v6($gi, ipv4to6($_SERVER['REMOTE_ADDR']))."</tinyboard>";
		}
	}

	if ($config['user_flag'] && isset($_POST['user_flag']) && !empty($_POST['user_flag'])) {
		$user_flag = $_POST['user_flag'];

		if (!isset($config['user_flags'][$user_flag])) {
			error(_('Invalid flag selection!'));
		}

		$flag_alt = isset($user_flag_alt) ? $user_flag_alt : $config['user_flags'][$user_flag];

		$post['body'] .= "\n<tinyboard flag>" . strtolower($user_flag) . "</tinyboard>" .
			"\n<tinyboard flag alt>" . $flag_alt . "</tinyboard>";
	}

	if ($config['allowed_tags'] && $post['op'] && isset($_POST['tag']) && isset($config['allowed_tags'][$_POST['tag']])) {
		$post['body'] .= "\n<tinyboard tag>" . $_POST['tag'] . "</tinyboard>";
	}

	if (!$dropped_post)
		if ($config['proxy_save'] && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$proxy = preg_replace("/[^0-9a-fA-F.,: ]/", '', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$post['body'] .= "\n<tinyboard proxy>".$proxy."</tinyboard>";
	}

	$post['body_nomarkup'] = strip_symbols($post['body']);

	$post['tracked_cites'] = markup($post['body'], true);


	if ($post['has_file']) {
		$allhashes = '';

		foreach ($post['files'] as $key => &$file) {
			if ($post['op'] && $config['allowed_ext_op']) {
				if (!in_array($file['extension'], $config['allowed_ext_op']))
					error($config['error']['unknownext']);
			}
			elseif (!in_array($file['extension'], $config['allowed_ext']) && !in_array($file['extension'], $config['allowed_ext_files']))
				error($config['error']['unknownext']);

			$file['is_an_image'] = !in_array($file['extension'], $config['allowed_ext_files']);

			// Truncate filename if it is too long
			$file['filename'] = mb_substr($file['filename'], 0, $config['max_filename_len']);

			$upload = $file['tmp_name'];

			if (!is_readable($upload))
				error($config['error']['nomove']);

			$hash = md5_hash_of_file($config, $upload);

			$file['hash'] = $hash;
			$allhashes .= $hash;
		}

		if (count ($post['files']) == 1) {
			$post['filehash'] = $hash;
		}
		else {
			$post['filehash'] = md5($allhashes);
		}
	}

	if (!hasPermission($config['mod']['bypass_filters'], $board['uri']) && !$dropped_post) {
		require_once 'inc/filters.php';

		do_filters($post);
	}

	if ($post['has_file']) {
		foreach ($post['files'] as $key => &$file) {
		if ($file['is_an_image']) {
			if ($config['ie_mime_type_detection'] !== false) {
				// Check IE MIME type detection XSS exploit
				$buffer = file_get_contents($upload, null, null, null, 255);
				if (preg_match($config['ie_mime_type_detection'], $buffer)) {
					undoImage($post);
					error($config['error']['mime_exploit']);
				}
			}

			require_once 'inc/image.php';

			// find dimensions of an image using GD
			if (!$size = @getimagesize($file['tmp_name'])) {
				error($config['error']['invalidimg']);
			}
			if (!in_array($size[2], array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_BMP, IMAGETYPE_WEBP))) {
				error($config['error']['invalidimg']);
			}
			if ($size[0] > $config['max_width'] || $size[1] > $config['max_height']) {
				error($config['error']['maxsize']);
			}


			if ($config['convert_auto_orient'] && ($file['extension'] == 'jpg' || $file['extension'] == 'jpeg')) {
				// The following code corrects the image orientation.
				// Currently only works with the 'convert' option selected but it could easily be expanded to work with the rest if you can be bothered.
				if (!($config['redraw_image'] || (($config['strip_exif'] && !$config['use_exiftool']) && ($file['extension'] == 'jpg' || $file['extension'] == 'jpeg')))) {
					if (in_array($config['thumb_method'], array('convert', 'convert+gifsicle', 'gm', 'gm+gifsicle'))) {
						$exif = @exif_read_data($file['tmp_name']);
						$gm = in_array($config['thumb_method'], array('gm', 'gm+gifsicle'));
						if (isset($exif['Orientation']) && $exif['Orientation'] != 1) {
							$error = shell_exec_error(($gm ? 'gm ' : '') . 'convert ' .
									escapeshellarg($file['tmp_name']) . ' -auto-orient ' . escapeshellarg($upload));

							if ($error)
								error(_('Could not auto-orient image!'), null, $error);
							$size = @getimagesize($file['tmp_name']);
							if ($config['strip_exif'])
								$file['exif_stripped'] = true;
						}
					}
				}
			}

			// create image object
			$image = new Image($file['tmp_name'], $file['extension'], $size);
			if ($image->size->width > $config['max_width'] || $image->size->height > $config['max_height']) {
				$image->delete();
				error($config['error']['maxsize']);
			}

			$file['width'] = $image->size->width;
			$file['height'] = $image->size->height;

			if ($config['spoiler_images'] && isset($_POST['spoiler'])) {
				$file['thumb'] = 'spoiler';

				$size = @getimagesize($config['spoiler_image']);
				$file['thumbwidth'] = $size[0];
				$file['thumbheight'] = $size[1];
			} elseif ($config['minimum_copy_resize'] &&
				$image->size->width <= $config['thumb_width'] &&
				$image->size->height <= $config['thumb_height'] &&
				$file['extension'] == ($config['thumb_ext'] ? $config['thumb_ext'] : $file['extension'])) {

				// Copy, because there's nothing to resize
				copy($file['tmp_name'], $file['thumb']);

				$file['thumbwidth'] = $image->size->width;
				$file['thumbheight'] = $image->size->height;
			} else {
				$thumb = $image->resize(
					$config['thumb_ext'] ? $config['thumb_ext'] : $file['extension'],
					$post['op'] ? $config['thumb_op_width'] : $config['thumb_width'],
					$post['op'] ? $config['thumb_op_height'] : $config['thumb_height']
				);

				$thumb->to($file['thumb']);

				$file['thumbwidth'] = $thumb->width;
				$file['thumbheight'] = $thumb->height;

				$thumb->_destroy();
			}

			$dont_copy_file = false;

			if ($config['redraw_image'] || (!@$file['exif_stripped'] && $config['strip_exif'] && ($file['extension'] == 'jpg' || $file['extension'] == 'jpeg'))) {
				if (!$config['redraw_image'] && $config['use_exiftool']) {
					try {
						$file['size'] = strip_image_metadata($file['tmp_name']);
					} catch (RuntimeException $e) {
						if ($config['syslog']) {
							_syslog(LOG_ERR, "Could not strip image metadata: {$e->getMessage()}");
							// Since EXIF metadata can countain sensible info, fail the request.
							error(_('Could not strip EXIF metadata!'), null, $error);
						}
					}
				} else {
					$image->to($file['file']);
					$dont_copy_file = true;
				}
			}
			$image->destroy();
		} else {
			// not an image
			$file['thumb'] = 'file';

			$size = @getimagesize(sprintf($config['file_thumb'],
				isset($config['file_icons'][$file['extension']]) ?
					$config['file_icons'][$file['extension']] : $config['file_icons']['default']));
			$file['thumbwidth'] = $size[0];
			$file['thumbheight'] = $size[1];
			$dont_copy_file = false;
		}

		if ($config['tesseract_ocr'] && $file['thumb'] != 'file') { // Let's OCR it!
			$fname = $file['tmp_name'];

			if ($file['height'] > 500 || $file['width'] > 500) {
				$fname = $file['thumb'];
			}

			if ($fname !== 'spoiler') { // We don't have that much CPU time, do we?
				try {
					$txt = ocr_image($config, $fname);
					if ($txt !== '') {
						// This one has an effect, that the body is appended to a post body. So you can write a correct
						// spamfilter.
						$post['body_nomarkup'] .= "<tinyboard ocr image $key>" . htmlspecialchars($value) . "</tinyboard>";
					}
				} catch (RuntimeException $e) {
					if ($config['syslog']) {
						_syslog(LOG_ERR, "Could not OCR image: {$e->getMessage()}");
					}
				}
			}
		}

		if (!$dont_copy_file) {
			if (isset($file['file_tmp'])) {
				if (!@rename($file['tmp_name'], $file['file']))
					error($config['error']['nomove']);
				chmod($file['file'], 0644);
			} elseif (!@move_uploaded_file($file['tmp_name'], $file['file']))
				error($config['error']['nomove']);
			}
		}

		if ($config['image_reject_repost']) {
			if ($p = getPostByHash($post['filehash'])) {
				undoImage($post);
				error(sprintf($config['error']['fileexists'],
					($post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root']) .
					($board['dir'] . $config['dir']['res'] .
						($p['thread'] ?
							$p['thread'] . '.html#' . $p['id']
						:
							$p['id'] . '.html'
						))
				));
			}
		} else if (!$post['op'] && $config['image_reject_repost_in_thread']) {
			if ($p = getPostByHashInThread($post['filehash'], $post['thread'])) {
				undoImage($post);
				error(sprintf($config['error']['fileexistsinthread'],
					($post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root']) .
					($board['dir'] . $config['dir']['res'] .
						($p['thread'] ?
							$p['thread'] . '.html#' . $p['id']
						:
							$p['id'] . '.html'
						))
				));
			}
		}
		}

	// Do filters again if OCRing
	if ($config['tesseract_ocr'] && !hasPermission($config['mod']['bypass_filters'], $board['uri']) && !$dropped_post) {
		do_filters($post);
	}

	if (!hasPermission($config['mod']['postunoriginal'], $board['uri']) && $config['robot_enable'] && checkRobot($post['body_nomarkup']) && !$dropped_post) {
		undoImage($post);
		if ($config['robot_mute']) {
			error(sprintf($config['error']['muted'], mute()));
		} else {
			error($config['error']['unoriginal']);
		}
	}

	// Remove board directories before inserting them into the database.
	if ($post['has_file']) {
		foreach ($post['files'] as $key => &$file) {
			$file['file_path'] = $file['file'];
			$file['thumb_path'] = $file['thumb'];
			$file['file'] = mb_substr($file['file'], mb_strlen($board['dir'] . $config['dir']['img']));
			if ($file['is_an_image'] && $file['thumb'] != 'spoiler')
				$file['thumb'] = mb_substr($file['thumb'], mb_strlen($board['dir'] . $config['dir']['thumb']));
		}
	}

	$post = (object)$post;
	$post->files = array_map(function($a) { return (object)$a; }, $post->files);

	$error = event('post', $post);
	$post->files = array_map(function($a) { return (array)$a; }, $post->files);

	if ($error) {
		undoImage((array)$post);
		error($error);
	}
	$post = (array)$post;

	$post['num_files'] = sizeof($post['files']);

	$post['id'] = $id = post($post);
	$post['slug'] = slugify($post);


	if ($dropped_post && $dropped_post['from_nntp']) {
		$query = prepare("INSERT INTO ``nntp_references`` (`board`, `id`, `message_id`, `message_id_digest`, `own`, `headers`) VALUES ".
			"(:board , :id , :message_id , :message_id_digest , false, :headers)");

		$query->bindValue(':board', $dropped_post['board']);
		$query->bindValue(':id', $id);
		$query->bindValue(':message_id', $dropped_post['msgid']);
		$query->bindValue(':message_id_digest', sha1($dropped_post['msgid']));
		$query->bindValue(':headers', $dropped_post['headers']);
		$query->execute() or error(db_error($query));
	}	// ^^^^^ For inbound posts  ^^^^^
	elseif ($config['nntpchan']['enabled'] && $config['nntpchan']['group']) {
		// vvvvv For outbound posts vvvvv

		require_once('inc/nntpchan/nntpchan.php');
		$msgid = gen_msgid($post['board'], $post['id']);

		list($headers, $files) = post2nntp($post, $msgid);

		$message = gen_nntp($headers, $files);

		$query = prepare("INSERT INTO ``nntp_references`` (`board`, `id`, `message_id`, `message_id_digest`, `own`, `headers`) VALUES ".
			"(:board , :id , :message_id , :message_id_digest , true , :headers)");

		$query->bindValue(':board', $post['board']);
		$query->bindValue(':id', $post['id']);
		$query->bindValue(':message_id', $msgid);
		$query->bindValue(':message_id_digest', sha1($msgid));
		$query->bindValue(':headers', json_encode($headers));
		$query->execute() or error(db_error($query));

		// Let's broadcast it!
		nntp_publish($message, $msgid);
	}

	insertFloodPost($post);

	// Handle cyclical threads
	if (!$post['op'] && isset($thread['cycle']) && $thread['cycle']) {
		// Query is a bit weird due to "This version of MariaDB doesn't yet support 'LIMIT & IN/ALL/ANY/SOME subquery'" (MariaDB Ver 15.1 Distrib 10.0.17-MariaDB, for Linux (x86_64))
		$query = prepare(sprintf('DELETE FROM ``posts_%s`` WHERE `thread` = :thread AND `id` NOT IN (SELECT `id` FROM (SELECT `id` FROM ``posts_%s`` WHERE `thread` = :thread ORDER BY `id` DESC LIMIT :limit) i)', $board['uri'], $board['uri']));
		$query->bindValue(':thread', $post['thread']);
		$query->bindValue(':limit', $config['cycle_limit'], PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
	}

	if (isset($post['antispam_hash'])) {
		incrementSpamHash($post['antispam_hash']);
	}

	if (isset($post['tracked_cites']) && !empty($post['tracked_cites'])) {
		$insert_rows = array();
		foreach ($post['tracked_cites'] as $cite) {
			$insert_rows[] = '(' .
				$pdo->quote($board['uri']) . ', ' . (int)$id . ', ' .
				$pdo->quote($cite[0]) . ', ' . (int)$cite[1] . ')';
		}
		query('INSERT INTO ``cites`` VALUES ' . implode(', ', $insert_rows)) or error(db_error());
	}

	if (!$post['op'] && strtolower($post['email']) != 'sage' && !$thread['sage'] && ($config['reply_limit'] == 0 || $numposts['replies']+1 < $config['reply_limit'])) {
		bumpThread($post['thread']);
	}

	if (isset($_SERVER['HTTP_REFERER'])) {
		// Tell Javascript that we posted successfully
		if (isset($_COOKIE[$config['cookies']['js']]))
			$js = json_decode($_COOKIE[$config['cookies']['js']]);
		else
			$js = (object) array();
		// Tell it to delete the cached post for referer
		$js->{$_SERVER['HTTP_REFERER']} = true;
		// Encode and set cookie
		setcookie($config['cookies']['js'], json_encode($js), 0, $config['cookies']['jail'] ? $config['cookies']['path'] : '/', null, false, false);
	}

	$root = $post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];

	if ($noko) {
		$redirect = $root . $board['dir'] . $config['dir']['res'] .
			link_for($post, false, false, $thread) . (!$post['op'] ? '#' . $id : '');

		if (!$post['op'] && isset($_SERVER['HTTP_REFERER'])) {
			$regex = array(
				'board' => str_replace('%s', '(\w{1,8})', preg_quote($config['board_path'], '/')),
				'page' => str_replace('%d', '(\d+)', preg_quote($config['file_page'], '/')),
				'page50' => '(' . str_replace('%d', '(\d+)', preg_quote($config['file_page50'], '/')) . '|' .
						  str_replace(array('%d', '%s'), array('(\d+)', '[a-z0-9-]+'), preg_quote($config['file_page50_slug'], '/')) . ')',
				'res' => preg_quote($config['dir']['res'], '/'),
			);

			if (preg_match('/\/' . $regex['board'] . $regex['res'] . $regex['page50'] . '([?&].*)?$/', $_SERVER['HTTP_REFERER'])) {
				$redirect = $root . $board['dir'] . $config['dir']['res'] .
					link_for($post, true, false, $thread) . (!$post['op'] ? '#' . $id : '');
			}
		}
	} else {
		$redirect = $root . $board['dir'] . $config['file_index'];

	}

	buildThread($post['op'] ? $id : $post['thread']);

	if ($config['syslog'])
		_syslog(LOG_INFO, 'New post: /' . $board['dir'] . $config['dir']['res'] .
			link_for($post) . (!$post['op'] ? '#' . $id : ''));

	if (!$post['mod']) header('X-Associated-Content: "' . $redirect . '"');


	if (!isset($_POST['json_response'])) {
		header('Location: ' . $redirect, true, $config['redirect_http']);
	} else {
		header('Content-Type: text/json; charset=utf-8');
		echo json_encode(array(
			'redirect' => $redirect,
			'noko' => $noko,
			'id' => $id
		));
	}

	if ($config['try_smarter'] && $post['op'])
		$build_pages = range(1, $config['max_pages']);

	if ($post['op'])
		clean($id);

	event('post-after', $post);

	buildIndex();

	// We are already done, let's continue our heavy-lifting work in the background (if we run off FastCGI)
	if (function_exists('fastcgi_finish_request'))
		@fastcgi_finish_request();

	if ($post['op'])
		rebuildThemes('post-thread', $board['uri']);
	else
		rebuildThemes('post', $board['uri']);

} elseif (isset($_POST['appeal'])) {
	if (!isset($_POST['ban_id']))
		error($config['error']['bot']);

	$ban_id = (int)$_POST['ban_id'];

	$ban = Bans::findSingle($_SERVER['REMOTE_ADDR'], $ban_id, $config['require_ban_view']);

	if (empty($ban)) {
		error($config['error']['noban']);
	}

	if ($ban['expires'] && $ban['expires'] - $ban['created'] <= $config['ban_appeals_min_length']) {
		error($config['error']['tooshortban']);
	}

	$query = query("SELECT `denied` FROM ``ban_appeals`` WHERE `ban_id` = $ban_id") or error(db_error());
	$ban_appeals = $query->fetchAll(PDO::FETCH_COLUMN);

	if (count($ban_appeals) >= $config['ban_appeals_max']) {
		error($config['error']['toomanyappeals']);
	}

	foreach ($ban_appeals as $is_denied) {
		if (!$is_denied) {
			error($config['error']['pendingappeal']);
		}
	}

	if (strlen($_POST['appeal']) > $config['ban_appeal_max_chars']) {
		error($config['error']['toolongappeal']);
	}

	$query = prepare("INSERT INTO ``ban_appeals`` VALUES (NULL, :ban_id, :time, :message, 0)");
	$query->bindValue(':ban_id', $ban_id, PDO::PARAM_INT);
	$query->bindValue(':time', time(), PDO::PARAM_INT);
	$query->bindValue(':message', $_POST['appeal']);
	$query->execute() or error(db_error($query));

	displayBan($ban);
} else {
	if (!file_exists($config['has_installed'])) {
		header('Location: install.php', true, $config['redirect_http']);
	} else {
		// They opened post.php in their browser manually.
		error($config['error']['nopost']);
	}
}
