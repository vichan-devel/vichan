<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

require 'inc/functions.php';
require 'inc/anti-bot.php';

// Fix for magic quotes
if (get_magic_quotes_gpc()) {
	function strip_array($var) {
		return is_array($var) ? array_map('strip_array', $var) : stripslashes($var);
	}
	
	$_GET = strip_array($_GET);
	$_POST = strip_array($_POST);
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
	
	// Check if banned
	checkBan($board['uri']);
	
	if (empty($delete))
		error($config['error']['nodelete']);
		
	foreach ($delete as &$id) {
		$query = prepare(sprintf("SELECT `thread`, `time`,`password` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
		$query->bindValue(':id', $id, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
		
		if ($post = $query->fetch(PDO::FETCH_ASSOC)) {
			if ($password != '' && $post['password'] != $password)
				error($config['error']['invalidpassword']);
			
			if ($post['time'] >= time() - $config['delete_time']) {
				error(sprintf($config['error']['delete_too_soon'], until($post['time'] + $config['delete_time'])));
			}
			
			if (isset($_POST['file'])) {
				// Delete just the file
				deleteFile($id);
			} else {
				// Delete entire post
				deletePost($id);
			}
			
			_syslog(LOG_INFO, 'Deleted post: ' .
				'/' . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $post['thread'] ? $post['thread'] : $id) . ($post['thread'] ? '#' . $id : '')
			);
		}
	}
	
	buildIndex();
	
	$is_mod = isset($_POST['mod']) && $_POST['mod'];
	$root = $is_mod ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];
	
	if (!$is_mod) header('X-Associated-Content: "' . $root . $board['dir'] . $config['file_index'] . '"');
	header('Location: ' . $root . $board['dir'] . $config['file_index'], true, $config['redirect_http']);

} elseif (isset($_POST['report'])) {
	if (!isset($_POST['board'], $_POST['password'], $_POST['reason']))
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
	
	// Check if banned
	checkBan($board['uri']);
	
	if (empty($report))
		error($config['error']['noreport']);
	
	if (count($report) > $config['report_limit'])
		error($config['error']['toomanyreports']);
	
	$reason = escape_markup_modifiers($_POST['reason']);
	markup($reason);
	
	foreach ($report as &$id) {
		$query = prepare(sprintf("SELECT `thread` FROM ``posts_%s`` WHERE `id` = :id", $board['uri']));
		$query->bindValue(':id', $id, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
		
		$thread = $query->fetchColumn();

		// If they are not reporting a reply, report the OP.
		if (!$thread) { 
			$thread = $id;
		}		

		if ($thread) {
			if ($config['syslog'])
				_syslog(LOG_INFO, 'Reported post: ' .
					'/' . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $thread ? $thread : $id) . ($thread ? '#' . $id : '') .
					' for "' . $reason . '"'
				);
			$query = prepare("INSERT INTO ``reports`` VALUES (NULL, :time, :ip, :board, :post, :reason)");
			$query->bindValue(':time', time(), PDO::PARAM_INT);
			$query->bindValue(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
			$query->bindValue(':board', $board['uri'], PDO::PARAM_INT);
			$query->bindValue(':post', $id, PDO::PARAM_INT);
			$query->bindValue(':reason', $reason, PDO::PARAM_STR);
			$query->execute() or error(db_error($query));
		}
	}
	
	$is_mod = isset($_POST['mod']) && $_POST['mod'];
	$root = $is_mod ? $config['root'] . $config['file_mod'] . '?/' : $config['root'];
	
	if (!$is_mod) header('X-Associated-Content: "' . $root . $board['dir'] . $config['file_index'] . '"');
	header('Location: ' . $root . $board['dir'] . $config['file_index'], true, $config['redirect_http']);
} elseif (isset($_POST['post'])) {
	
	if (!isset($_POST['body'], $_POST['board']))
		error($config['error']['bot']);
	
	if (!isset($_POST['name']))
		$_POST['name'] = $config['anonymous'];
	
	if (!isset($_POST['email']))
		$_POST['email'] = '';
	
	if (!isset($_POST['subject']))
		$_POST['subject'] = '';
	
	if (!isset($_POST['password']))
		$_POST['password'] = '';
	
	$post = array('board' => $_POST['board']);
	
	if (isset($_POST['thread'])) {
		$post['op'] = false;
		$post['thread'] = round($_POST['thread']);
	} elseif ($config['quick_reply'] && isset($_POST['quick-reply'])) {
		$post['op'] = false;
		$post['thread'] = round($_POST['quick-reply']);
	} else
		$post['op'] = true;
	
	if (!(($post['op'] && $_POST['post'] == $config['button_newtopic']) ||
	    (!$post['op'] && $_POST['post'] == $config['button_reply'])))
		error($config['error']['bot']);
	
	// Check the referrer
	if (!isset($_SERVER['HTTP_REFERER']) || !preg_match($config['referer_match'], urldecode($_SERVER['HTTP_REFERER'])))
		error($config['error']['referer']);
	
	checkDNSBL();
		
	// Check if board exists
	if (!openBoard($post['board']))
		error($config['error']['noboard']);
	
	// Check if banned
	checkBan($board['uri']);
	
	// Check for CAPTCHA right after opening the board so the "return" link is in there
	if ($config['recaptcha']) {
		if (!isset($_POST['recaptcha_challenge_field']) || !isset($_POST['recaptcha_response_field']))
			error($config['error']['bot']);
		// Check what reCAPTCHA has to say...
		$resp = recaptcha_check_answer($config['recaptcha_private'],
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']);
		if (!$resp->is_valid) {
			error($config['error']['captcha']);
		}
	}
	
	if ($post['mod'] = isset($_POST['mod']) && $_POST['mod']) {
		require 'inc/mod.php';
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
		$post['antispam_hash'] = checkSpam(array($board['uri'], isset($post['thread']) && !($config['quick_reply'] && isset($_POST['quick-reply'])) ? $post['thread'] : ($config['try_smarter'] && isset($_POST['page']) ? 0 - (int)$_POST['page'] : null)));
		if ($post['antispam_hash'] === true)
			error($config['error']['spam']);
	}
	
	if ($config['robot_enable'] && $config['robot_mute']) {
		checkMute();
	}
	
	//Check if thread exists
	if (!$post['op']) {
		$query = prepare(sprintf("SELECT `sticky`,`locked`,`sage` FROM ``posts_%s`` WHERE `id` = :id AND `thread` IS NULL LIMIT 1", $board['uri']));
		$query->bindValue(':id', $post['thread'], PDO::PARAM_INT);
		$query->execute() or error(db_error());
		
		if (!$thread = $query->fetch(PDO::FETCH_ASSOC)) {
			// Non-existant
			error($config['error']['nonexistant']);
		}
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
	
	// Check for a file
	if ($post['op'] && !isset($post['no_longer_require_an_image_for_op'])) {
		if (!isset($_FILES['file']['tmp_name']) || $_FILES['file']['tmp_name'] == '' && $config['force_image_op'])
			error($config['error']['noimage']);
	}
	
	$post['name'] = $_POST['name'] != '' ? $_POST['name'] : $config['anonymous'];
	$post['subject'] = $_POST['subject'];
	$post['email'] = str_replace(' ', '%20', htmlspecialchars($_POST['email']));
	$post['body'] = $_POST['body'];
	$post['password'] = $_POST['password'];
	$post['has_file'] = !isset($post['embed']) && (($post['op'] && !isset($post['no_longer_require_an_image_for_op']) && $config['force_image_op']) || (isset($_FILES['file']) && $_FILES['file']['tmp_name'] != ''));
	
	if ($post['has_file'])
		$post['filename'] = urldecode(get_magic_quotes_gpc() ? stripslashes($_FILES['file']['name']) : $_FILES['file']['name']);
	
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
		
	if ($post['has_file']) {
		$size = $_FILES['file']['size'];
		if ($size > $config['max_filesize'])
			error(sprintf3($config['error']['filesize'], array(
				'sz' => number_format($size),
				'filesz' => number_format($size),
				'maxsz' => number_format($config['max_filesize'])
			)));
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
	$post['trip'] = isset($trip[1]) ? $trip[1] : '';
	
	if (strtolower($post['email']) == 'noko') {
		$noko = true;
		$post['email'] = '';
	} else $noko = false;
	
	if ($post['has_file']) {
		$post['extension'] = strtolower(mb_substr($post['filename'], mb_strrpos($post['filename'], '.') + 1));
		if (isset($config['filename_func']))
			$post['file_id'] = $config['filename_func']($post);
		else
			$post['file_id'] = time() . substr(microtime(), 2, 3);
		
		$post['file'] = $board['dir'] . $config['dir']['img'] . $post['file_id'] . '.' . $post['extension'];
		$post['thumb'] = $board['dir'] . $config['dir']['thumb'] . $post['file_id'] . '.' . ($config['thumb_ext'] ? $config['thumb_ext'] : $post['extension']);
	}
	
	if ($config['strip_combining_chars']) {
		$post['name'] = strip_combining_chars($post['name']);
		$post['email'] = strip_combining_chars($post['email']);
		$post['subject'] = strip_combining_chars($post['subject']);
		$post['body'] = strip_combining_chars($post['body']);
	}
	
	// Check string lengths
	if (mb_strlen($post['name']) > 35)
		error(sprintf($config['error']['toolong'], 'name'));	
	if (mb_strlen($post['email']) > 40)
		error(sprintf($config['error']['toolong'], 'email'));
	if (mb_strlen($post['subject']) > 100)
		error(sprintf($config['error']['toolong'], 'subject'));
	if (!$mod && mb_strlen($post['body']) > $config['max_body'])
		error($config['error']['toolong_body']);
	if (mb_strlen($post['password']) > 20)
		error(sprintf($config['error']['toolong'], 'password'));
		
	wordfilters($post['body']);
	$post['body'] = escape_markup_modifiers($post['body']);
	
	if ($mod && isset($post['raw']) && $post['raw']) {
		$post['body'] = '<tinyboard raw html>' . $post['body'] . '</tinyboard>';
	}
	
	if (mysql_version() >= 50503) {
		$post['body_nomarkup'] = $post['body']; // Assume we're using the utf8mb4 charset
	} else {
		// MySQL's `utf8` charset only supports up to 3-byte symbols
		// Remove anything >= 0x010000
		
		$chars = preg_split('//u', $post['body'], -1, PREG_SPLIT_NO_EMPTY);
		$post['body_nomarkup'] = '';
		foreach ($chars as $char) {
			$o = 0;
			$ord = ordutf8($char, $o);
			if ($ord >= 0x010000)
				continue;
			$post['body_nomarkup'] .= $char;
		}
	}
	
	$post['tracked_cites'] = markup($post['body'], true);
	
	// Check for a flood
	if (!hasPermission($config['mod']['flood'], $board['uri']) && checkFlood($post)) {
		error($config['error']['flood']);
	}
	
	require_once 'inc/filters.php';
	
	do_filters($post);
	
	if ($post['has_file']) {
		if (!in_array($post['extension'], $config['allowed_ext']) && !in_array($post['extension'], $config['allowed_ext_files']))
			error($config['error']['unknownext']);
		
		$is_an_image = !in_array($post['extension'], $config['allowed_ext_files']);
		
		// Truncate filename if it is too long
		$post['filename'] = mb_substr($post['filename'], 0, $config['max_filename_len']);
		
		$upload = $_FILES['file']['tmp_name'];
		
		if (!is_readable($upload))
			error($config['error']['nomove']);
		
		$post['filehash'] = $config['file_hash']($upload);
		$post['filesize'] = filesize($upload);
		
		if ($is_an_image && $config['ie_mime_type_detection'] !== false) {
			// Check IE MIME type detection XSS exploit
			$buffer = file_get_contents($upload, null, null, null, 255);
			if (preg_match($config['ie_mime_type_detection'], $buffer)) {
				undoImage($post);
				error($config['error']['mime_exploit']);
			}
			
			require_once 'inc/image.php';
			
			// find dimensions of an image using GD
			if (!$size = @getimagesize($upload)) {
				error($config['error']['invalidimg']);
			}
			if ($size[0] > $config['max_width'] || $size[1] > $config['max_height']) {
				error($config['error']['maxsize']);
			}
			
			
			if ($config['convert_auto_orient'] && ($post['extension'] == 'jpg' || $post['extension'] == 'jpeg')) {
				// The following code corrects the image orientation.
				// Currently only works with the 'convert' option selected but it could easily be expanded to work with the rest if you can be bothered.
				if (!($config['redraw_image'] || (($config['strip_exif'] && !$config['use_exiftool']) && ($post['extension'] == 'jpg' || $post['extension'] == 'jpeg')))) {
					if (in_array($config['thumb_method'], array('convert', 'convert+gifsicle', 'gm', 'gm+gifsicle'))) {
						$exif = @exif_read_data($upload);
						$gm = in_array($config['thumb_method'], array('gm', 'gm+gifsicle'));
						if (isset($exif['Orientation']) && $exif['Orientation'] != 1) {
							if ($config['convert_manual_orient']) {
								$error = shell_exec_error(($gm ? 'gm ' : '') . 'convert ' .
									escapeshellarg($upload) . ' ' .
									ImageConvert::jpeg_exif_orientation(false, $exif) . ' ' .
									($config['strip_exif'] ? '+profile "*"' :
										($config['use_exiftool'] ? '' : '+profile "*"')
									) . ' ' .
									escapeshellarg($upload));
								if ($config['use_exiftool'] && !$config['strip_exif']) {
									if ($exiftool_error = shell_exec_error(
										'exiftool -q -orientation=1 -n ' . escapeshellarg($upload)))
										error('exiftool failed!', null, $exiftool_error);
								} else {
									// TODO: Find another way to remove the Orientation tag from the EXIF profile
									// without needing `exiftool`.
								}
							} else {
								$error = shell_exec_error(($gm ? 'gm ' : '') . 'convert ' .
										escapeshellarg($upload) . ' -auto-orient ' . escapeshellarg($upload));
							}
							if ($error)
								error('Could not auto-orient image!', null, $error);
							$size = @getimagesize($upload);
							if ($config['strip_exif'])
								$post['exif_stripped'] = true;
						}
					}
				}
			}
			
			// create image object
			$image = new Image($upload, $post['extension'], $size);
			if ($image->size->width > $config['max_width'] || $image->size->height > $config['max_height']) {
				$image->delete();
				error($config['error']['maxsize']);
			}
			
			$post['width'] = $image->size->width;
			$post['height'] = $image->size->height;
			
			if ($config['spoiler_images'] && isset($_POST['spoiler'])) {
				$post['thumb'] = 'spoiler';
				
				$size = @getimagesize($config['spoiler_image']);
				$post['thumbwidth'] = $size[0];
				$post['thumbheight'] = $size[1];
			} elseif ($config['minimum_copy_resize'] &&
				$image->size->width <= $config['thumb_width'] &&
				$image->size->height <= $config['thumb_height'] &&
				$post['extension'] == ($config['thumb_ext'] ? $config['thumb_ext'] : $post['extension'])) {
			
				// Copy, because there's nothing to resize
				copy($upload, $post['thumb']);
			
				$post['thumbwidth'] = $image->size->width;
				$post['thumbheight'] = $image->size->height;
			} else {
				$thumb = $image->resize(
					$config['thumb_ext'] ? $config['thumb_ext'] : $post['extension'],
					$post['op'] ? $config['thumb_op_width'] : $config['thumb_width'],
					$post['op'] ? $config['thumb_op_height'] : $config['thumb_height']
				);
				
				$thumb->to($post['thumb']);
			
				$post['thumbwidth'] = $thumb->width;
				$post['thumbheight'] = $thumb->height;
			
				$thumb->_destroy();
			}
			
			if ($config['redraw_image'] || (!@$post['exif_stripped'] && $config['strip_exif'] && ($post['extension'] == 'jpg' || $post['extension'] == 'jpeg'))) {
				if (!$config['redraw_image'] && $config['use_exiftool']) {
					if($error = shell_exec_error('exiftool -ignoreMinorErrors -q -q -all= ' . escapeshellarg($upload)))
						error('Could not strip EXIF metadata!', null, $error);
				} else {
					$image->to($post['file']);
					$dont_copy_file = true;
				}
			}
			$image->destroy();
		} else {
			// not an image
			//copy($config['file_thumb'], $post['thumb']);
			$post['thumb'] = 'file';
			
			$size = @getimagesize($config['file_thumb']);
			$post['thumbwidth'] = $size[0];
			$post['thumbheight'] = $size[1];
		}
		
		if (!isset($dont_copy_file) || !$dont_copy_file) {
			if (!@move_uploaded_file($upload, $post['file']))
				error($config['error']['nomove']);
		}
	}
	
	if ($post['has_file']) {
		if ($config['image_reject_repost']) {
			if ($p = getPostByHash($post['filehash'])) {
				undoImage($post);
				error(sprintf($config['error']['fileexists'], 
					$post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root'] .
					$board['dir'] . $config['dir']['res'] .
						($p['thread'] ?
							$p['thread'] . '.html#' . $p['id']
						:
							$p['id'] . '.html'
						)
				));
			}
		} else if (!$post['op'] && $config['image_reject_repost_in_thread']) {
			if ($p = getPostByHashInThread($post['filehash'], $post['thread'])) {
				undoImage($post);
				error(sprintf($config['error']['fileexistsinthread'], 
					$post['mod'] ? $config['root'] . $config['file_mod'] . '?/' : $config['root'] .
					$board['dir'] . $config['dir']['res'] .
						($p['thread'] ?
							$p['thread'] . '.html#' . $p['id']
						:
							$p['id'] . '.html'
						)
				));
			}
		}
	}
	
	if (!hasPermission($config['mod']['postunoriginal'], $board['uri']) && $config['robot_enable'] && checkRobot($post['body_nomarkup'])) {
		undoImage($post);
		if ($config['robot_mute']) {
			error(sprintf($config['error']['muted'], mute()));
		} else {
			error($config['error']['unoriginal']);
		}
	}
	
	// Remove board directories before inserting them into the database.
	if ($post['has_file']) {
		$post['file_path'] = $post['file'];
		$post['file'] = mb_substr($post['file'], mb_strlen($board['dir'] . $config['dir']['img']));
		if ($is_an_image && $post['thumb'] != 'spoiler')
			$post['thumb'] = mb_substr($post['thumb'], mb_strlen($board['dir'] . $config['dir']['thumb']));
	}
	
	$post = (object)$post;
	if ($error = event('post', $post)) {
		undoImage((array)$post);
		error($error);
	}
	$post = (array)$post;
	
	$post['id'] = $id = post($post);
	
	if (isset($post['antispam_hash'])) {
		incrementSpamHash($post['antispam_hash']);
	}
	
	if (isset($post['tracked_cites'])) {
		foreach ($post['tracked_cites'] as $cite) {
			$query = prepare('INSERT INTO ``cites`` VALUES (:board, :post, :target_board, :target)');
			$query->bindValue(':board', $board['uri']);
			$query->bindValue(':post', $id, PDO::PARAM_INT);
			$query->bindValue(':target_board',$cite[0]);
			$query->bindValue(':target', $cite[1], PDO::PARAM_INT);
			$query->execute() or error(db_error($query));
		}
	}
	
	if (!$post['op'] && strtolower($post['email']) != 'sage' && !$thread['sage'] && ($config['reply_limit'] == 0 || $numposts['replies']+1 < $config['reply_limit'])) {
		bumpThread($post['thread']);
	}
	
	buildThread($post['op'] ? $id : $post['thread']);
	
	if ($config['try_smarter'] && $post['op'])
		$build_pages = range(1, $config['max_pages']);
	
	if ($post['op'])
		clean();
	
	event('post-after', $post);
	
	buildIndex();
	
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
	
	if ($config['always_noko'] || $noko) {
		$redirect = $root . $board['dir'] . $config['dir']['res'] .
			sprintf($config['file_page'], $post['op'] ? $id:$post['thread']) . (!$post['op'] ? '#' . $id : '');
	} else {
		$redirect = $root . $board['dir'] . $config['file_index'];
		
	}
	
	if ($config['syslog'])
		_syslog(LOG_INFO, 'New post: /' . $board['dir'] . $config['dir']['res'] .
			sprintf($config['file_page'], $post['op'] ? $id : $post['thread']) . (!$post['op'] ? '#' . $id : ''));
	
	if (!$post['mod']) header('X-Associated-Content: "' . $redirect . '"');

	if ($post['op'])
		rebuildThemes('post-thread', $board['uri']);
	else
		rebuildThemes('post', $board['uri']);
	
	header('Location: ' . $redirect, true, $config['redirect_http']);
} else {
	if (!file_exists($config['has_installed'])) {
		header('Location: install.php', true, $config['redirect_http']);
	} else {
		// They opened post.php in their browser manually.
		error($config['error']['nopost']);
	}
}

