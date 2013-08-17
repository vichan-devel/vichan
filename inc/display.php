<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

if (realpath($_SERVER['SCRIPT_FILENAME']) == str_replace('\\', '/', __FILE__)) {
	// You cannot request this file directly.
	exit;
}

/* 
	joaoptm78@gmail.com
	http://www.php.net/manual/en/function.filesize.php#100097
*/
function format_bytes($size) {
	$units = array(' B', ' KB', ' MB', ' GB', ' TB');
	for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
	return round($size, 2).$units[$i];
}

function doBoardListPart($list, $root) {
	global $config;
	
	$body = '';
	foreach ($list as $board) {
		if (is_array($board))
			// $body .= ' [' . doBoardListPart($board, $root) . '] ';
			$body .= ' <span class="sub">[' . doBoardListPart($board, $root) . ']</span> ';
		else {
			if (($key = array_search($board, $list)) && gettype($key) == 'string') {
				$body .= ' <a href="' . $board . '">' . $key . '</a> /';
			} else {			
				$body .= ' <a href="' . $root . $board . '/' . $config['file_index'] . '">' . $board . '</a> /';
			}
		}
	}
	$body = preg_replace('/\/$/', '', $body);
	
	return $body;
}

function createBoardlist($mod=false) {
	global $config;
	
	if (!isset($config['boards'])) return array('top'=>'','bottom'=>'');
	
	$body = doBoardListPart($config['boards'], $mod?'?/':$config['root']);

	if ($config['boardlist_wrap_bracket'] && !preg_match('/\] $/', $body))
		$body = '[' . $body . ']';
	
	$body = trim($body);
	
	return array(
		'top' => '<div class="boardlist">' . $body . '</div>',
		'bottom' => '<div class="boardlist bottom">' . $body . '</div>'
	);
}

function error($message, $priority = true, $debug_stuff = false) {
	global $board, $mod, $config, $db_error;
	
	if ($config['syslog'] && $priority !== false) {
		// Use LOG_NOTICE instead of LOG_ERR or LOG_WARNING because most error message are not significant.
		_syslog($priority !== true ? $priority : LOG_NOTICE, $message);
	}
	
	if (defined('STDIN')) {
		// Running from CLI
		die('Error: ' . $message . "\n");
	}

	if ($config['debug'] && isset($db_error)) {
		$debug_stuff = array_combine(array('SQLSTATE', 'Error code', 'Error message'), $db_error);
	}

	// Return the bad request header, necessary for AJAX posts
	http_response_code(400);
	
	die(Element('page.html', array(
		'config' => $config,
		'title' => _('Error'),
		'subtitle' => _('An error has occured.'),
		'body' => Element('error.html', array(
			'config' => $config,
			'message' => $message,
			'mod' => $mod,
			'board' => isset($board) ? $board : false,
			'debug' => is_array($debug_stuff) ? str_replace("\n", '&#10;', utf8tohtml(print_r($debug_stuff, true))) : utf8tohtml($debug_stuff)
		))
	)));
}

function loginForm($error=false, $username=false, $redirect=false) {
	global $config;
	
	die(Element('page.html', array(
		'index' => $config['root'],
		'title' => _('Login'),
		'config' => $config,
		'body' => Element('login.html', array(
			'config'=>$config,
			'error'=>$error,
			'username'=>utf8tohtml($username),
			'redirect'=>$redirect
			)
		)
	)));
}

function pm_snippet($body, $len=null) {
	global $config;
	
	if (!isset($len))
		$len = &$config['mod']['snippet_length'];
	
	// Replace line breaks with some whitespace
	$body = str_replace('<br/>', '  ', $body);
	
	// Strip tags
	$body = strip_tags($body);
	
	// Unescape HTML characters, to avoid splitting them in half
	$body = html_entity_decode($body, ENT_COMPAT, 'UTF-8');
	
	// calculate strlen() so we can add "..." after if needed
	$strlen = mb_strlen($body);
	
	$body = mb_substr($body, 0, $len);
	
	// Re-escape the characters.
	return '<em>' . utf8tohtml($body) . ($strlen > $len ? '&hellip;' : '') . '</em>';
}

function capcode($cap) {
	global $config;
	
	if (!$cap)
		return false;
	
	$capcode = array();
	if (isset($config['custom_capcode'][$cap])) {
		if (is_array($config['custom_capcode'][$cap])) {
			$capcode['cap'] = sprintf($config['custom_capcode'][$cap][0], $cap);
			if (isset($config['custom_capcode'][$cap][1]))
				$capcode['name'] = $config['custom_capcode'][$cap][1];
			if (isset($config['custom_capcode'][$cap][2]))
				$capcode['trip'] = $config['custom_capcode'][$cap][2];
		} else {
			$capcode['cap'] = sprintf($config['custom_capcode'][$cap], $cap);
		}
	} else {
		$capcode['cap'] = sprintf($config['capcode'], $cap);
	}
	
	return $capcode;
}

function truncate($body, $url, $max_lines = false, $max_chars = false) {
	global $config;
	
	if ($max_lines === false)
		$max_lines = $config['body_truncate'];
	if ($max_chars === false)
		$max_chars = $config['body_truncate_char'];
	
	// We don't want to risk truncating in the middle of an HTML comment.
	// It's easiest just to remove them all first.
	$body = preg_replace('/<!--.*?-->/s', '', $body);
	
	$original_body = $body;
	
	$lines = substr_count($body, '<br/>');
	
	// Limit line count
	if ($lines > $max_lines) {
		if (preg_match('/(((.*?)<br\/>){' . $max_lines . '})/', $body, $m))
			$body = $m[0];
	}
	
	$body = mb_substr($body, 0, $max_chars);
	
	if ($body != $original_body) {
		// Remove any corrupt tags at the end
		$body = preg_replace('/<([\w]+)?([^>]*)?$/', '', $body);
		
		// Open tags
		if (preg_match_all('/<([\w]+)[^>]*>/', $body, $open_tags)) {
			
			$tags = array();
			for ($x=0;$x<count($open_tags[0]);$x++) {
				if (!preg_match('/\/(\s+)?>$/', $open_tags[0][$x]))
					$tags[] = $open_tags[1][$x];
			}
			
			// List successfully closed tags
			if (preg_match_all('/(<\/([\w]+))>/', $body, $closed_tags)) {
				for ($x=0;$x<count($closed_tags[0]);$x++) {
					unset($tags[array_search($closed_tags[2][$x], $tags)]);
				}
			}
			
			// remove broken HTML entity at the end (if existent)
			$body = preg_replace('/&[^;]+$/', '', $body);
			
			$tags_no_close_needed = array("colgroup", "dd", "dt", "li", "optgroup", "option", "p", "tbody", "td", "tfoot", "th", "thead", "tr", "br", "img");
			
			// Close any open tags
			foreach ($tags as &$tag) {
				if (!in_array($tag, $tags_no_close_needed))
					$body .= "</{$tag}>";
			}
		} else {
			// remove broken HTML entity at the end (if existent)
			$body = preg_replace('/&[^;]*$/', '', $body);
		}
		
		$body .= '<span class="toolong">'.sprintf(_('Post too long. Click <a href="%s">here</a> to view the full text.'), $url).'</span>';
	}
	
	return $body;
}

function bidi_cleanup($str){
	# Removes all embedded RTL and LTR unicode formatting blocks in a string so that
	# it can be used inside another without controlling its direction.
	# More info: http://www.iamcal.com/understanding-bidirectional-text/
	#
	# LRE - U+202A - 0xE2 0x80 0xAA
	# RLE - U+202B - 0xE2 0x80 0xAB
	# LRO - U+202D - 0xE2 0x80 0xAD
	# RLO - U+202E - 0xE2 0x80 0xAE
	#
	# PDF - U+202C - 0xE2 0x80 0xAC
	#
	$explicits	= '\xE2\x80\xAA|\xE2\x80\xAB|\xE2\x80\xAD|\xE2\x80\xAE';
	$pdf		= '\xE2\x80\xAC';

	$str = preg_replace("!(?<explicits>$explicits)|(?<pdf>$pdf)!", '', $str);
	return $str;
}

function secure_link_confirm($text, $title, $confirm_message, $href) {
	global $config;

	return '<a onclick="if (event.which==2) return true;if (confirm(\'' . htmlentities(addslashes($confirm_message)) . '\')) document.location=\'?/' . htmlspecialchars(addslashes($href . '/' . make_secure_link_token($href))) . '\';return false;" title="' . htmlentities($title) . '" href="?/' . $href . '">' . $text . '</a>';
}
function secure_link($href) {
	return $href . '/' . make_secure_link_token($href);
}

function embed_html($link) {
	global $config;
	
	foreach ($config['embedding'] as $embed) {
		if ($html = preg_replace($embed[0], $embed[1], $link)) {
				if ($html == $link)
					continue; // Nope
			
			$html = str_replace('%%tb_width%%', $config['embed_width'], $html);
			$html = str_replace('%%tb_height%%', $config['embed_height'], $html);
			
			return $html;
		}
	}
	
	if ($link[0] == '<') {
		// Prior to v0.9.6-dev-8, HTML code for embedding was stored in the database instead of the link.
		return $link;
	}
	
	return 'Embedding error.';
}

class Post {
	public function __construct($post, $root=null, $mod=false) {
		global $config;
		if (!isset($root))
			$root = &$config['root'];
		
		foreach ($post as $key => $value) {
			$this->{$key} = $value;
		}
		
		$this->subject = utf8tohtml($this->subject);
		$this->name = utf8tohtml($this->name);
		$this->mod = $mod;
		$this->root = $root;
		
		if ($this->embed)
			$this->embed = embed_html($this->embed);
		
		$this->modifiers = extract_modifiers($this->body_nomarkup);
		
		if ($this->mod)
			// Fix internal links
			// Very complicated regex
			$this->body = preg_replace(
				'/<a((([a-zA-Z]+="[^"]+")|[a-zA-Z]+=[a-zA-Z]+|\s)*)href="' . preg_quote($config['root'], '/') . '(' . sprintf(preg_quote($config['board_path'], '/'), $config['board_regex']) . ')/u',
				'<a $1href="?/$4',
				$this->body
			);
	}
	public function link($pre = '') {
		global $config, $board;
		
		return $this->root . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $this->thread) . '#' . $pre . $this->id;
	}
	public function postControls() {
		global $board, $config;
		
		$built = '';
		if ($this->mod) {
			// Mod controls (on posts)
			
			// Delete
			if (hasPermission($config['mod']['delete'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_delete'], 'Delete', 'Are you sure you want to delete this?', $board['dir'] . 'delete/' . $this->id);
			
			// Delete all posts by IP
			if (hasPermission($config['mod']['deletebyip'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_deletebyip'], 'Delete all posts by IP', 'Are you sure you want to delete all posts by this IP address?', $board['dir'] . 'deletebyip/' . $this->id);
			
			// Delete all posts by IP (global)
			if (hasPermission($config['mod']['deletebyip_global'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_deletebyip_global'], 'Delete all posts by IP across all boards', 'Are you sure you want to delete all posts by this IP address, across all boards?', $board['dir'] . 'deletebyip/' . $this->id . '/global');
			
			// Ban
			if (hasPermission($config['mod']['ban'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Ban').'" href="?/' . $board['dir'] . 'ban/' . $this->id . '">' . $config['mod']['link_ban'] . '</a>';
			
			// Ban & Delete
			if (hasPermission($config['mod']['bandelete'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Ban & Delete').'" href="?/' . $board['dir'] . 'ban&amp;delete/' . $this->id . '">' . $config['mod']['link_bandelete'] . '</a>';
			
			// Delete file (keep post)
			if (!empty($this->file) && hasPermission($config['mod']['deletefile'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_deletefile'], _('Delete file'), _('Are you sure you want to delete this file?'), $board['dir'] . 'deletefile/' . $this->id);
			
			// Spoiler file (keep post)
			if (!empty($this->file)  && $this->file != "deleted" && $this->file != null && $this->thumb != 'spoiler' && hasPermission($config['mod']['spoilerimage'], $board['uri'], $this->mod) && $config['spoiler_images'])
				$built .= ' ' . secure_link_confirm($config['mod']['link_spoilerimage'], _('Spoiler File'), _('Are you sure you want to spoiler this file?'), $board['uri'] . '/spoiler/' . $this->id);

			// Move post
			if (hasPermission($config['mod']['move'], $board['uri'], $this->mod) && $config['move_replies'])
				$built .= ' <a title="'._('Move reply to another board').'" href="?/' . $board['uri'] . '/move_reply/' . $this->id . '">' . $config['mod']['link_move'] . '</a>';

			// Edit post
			if (hasPermission($config['mod']['editpost'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Edit post').'" href="?/' . $board['dir'] . 'edit' . ($config['mod']['raw_html_default'] ? '_raw' : '') . '/' . $this->id . '">' . $config['mod']['link_editpost'] . '</a>';

			
			if (!empty($built))
				$built = '<span class="controls">' . $built . '</span>';
		}
		return $built;
	}
	
	public function ratio() {
		return fraction($this->filewidth, $this->fileheight, ':');
	}
	
	public function build($index=false) {
		global $board, $config;
		
		return Element('post_reply.html', array('config' => $config, 'board' => $board, 'post' => &$this, 'index' => $index));
	}
};

class Thread {
	public function __construct($post, $root = null, $mod = false, $hr = true) {
		global $config;
		if (!isset($root))
			$root = &$config['root'];
		
		foreach ($post as $key => $value) {
			$this->{$key} = $value;
		}
		
		$this->subject = utf8tohtml($this->subject);
		$this->name = utf8tohtml($this->name);
		$this->mod = $mod;
		$this->root = $root;
		$this->hr = $hr;

		$this->posts = array();
		$this->omitted = 0;
		$this->omitted_images = 0;
		
		if ($this->embed)
			$this->embed = embed_html($this->embed);
		
		$this->modifiers = extract_modifiers($this->body_nomarkup);
		
		if ($this->mod)
			// Fix internal links
			// Very complicated regex
			$this->body = preg_replace(
				'/<a((([a-zA-Z]+="[^"]+")|[a-zA-Z]+=[a-zA-Z]+|\s)*)href="' . preg_quote($config['root'], '/') . '(' . sprintf(preg_quote($config['board_path'], '/'), $config['board_regex']) . ')/u',
				'<a $1href="?/$4',
				$this->body
			);
	}
	public function link($pre = '') {
		global $config, $board;
		
		return $this->root . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], $this->id) . '#' . $pre . $this->id;
	}
	public function add(Post $post) {
		$this->posts[] = $post;
	}
	public function postCount() {
	       return count($this->posts) + $this->omitted;
	}
	public function postControls() {
		global $board, $config;
		
		$built = '';
		if ($this->mod) {
			// Mod controls (on posts)
			// Delete
			if (hasPermission($config['mod']['delete'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_delete'], _('Delete'), _('Are you sure you want to delete this?'), $board['dir'] . 'delete/' . $this->id);
			
			// Delete all posts by IP
			if (hasPermission($config['mod']['deletebyip'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_deletebyip'], _('Delete all posts by IP'), _('Are you sure you want to delete all posts by this IP address?'), $board['dir'] . 'deletebyip/' . $this->id);
			
			// Delete all posts by IP (global)
			if (hasPermission($config['mod']['deletebyip_global'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_deletebyip_global'], _('Delete all posts by IP across all boards'), _('Are you sure you want to delete all posts by this IP address, across all boards?'), $board['dir'] . 'deletebyip/' . $this->id . '/global');
			
			// Ban
			if (hasPermission($config['mod']['ban'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Ban').'" href="?/' . $board['dir'] . 'ban/' . $this->id . '">' . $config['mod']['link_ban'] . '</a>';
			
			// Ban & Delete
			if (hasPermission($config['mod']['bandelete'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Ban & Delete').'" href="?/' . $board['dir'] . 'ban&amp;delete/' . $this->id . '">' . $config['mod']['link_bandelete'] . '</a>';
			
			// Delete file (keep post)
			if (!empty($this->file) && $this->file != 'deleted' && hasPermission($config['mod']['deletefile'], $board['uri'], $this->mod))
				$built .= ' ' . secure_link_confirm($config['mod']['link_deletefile'], _('Delete file'), _('Are you sure you want to delete this file?'), $board['dir'] . 'deletefile/' . $this->id);

			// Spoiler file (keep post)
			if (!empty($this->file)  && $this->file != "deleted" && $this->file != null && $this->thumb != 'spoiler' && hasPermission($config['mod']['spoilerimage'], $board['uri'], $this->mod) && $config['spoiler_images'])
				$built .= ' ' . secure_link_confirm($config['mod']['link_spoilerimage'], _('Spoiler File'), _('Are you sure you want to spoiler this file?'), $board['uri'] . '/spoiler/' . $this->id);
			
			// Sticky
			if (hasPermission($config['mod']['sticky'], $board['uri'], $this->mod))
				if ($this->sticky)
					$built .= ' <a title="'._('Make thread not sticky').'" href="?/' . secure_link($board['dir'] . 'unsticky/' . $this->id) . '">' . $config['mod']['link_desticky'] . '</a>';
				else
					$built .= ' <a title="'._('Make thread sticky').'" href="?/' . secure_link($board['dir'] . 'sticky/' . $this->id) . '">' . $config['mod']['link_sticky'] . '</a>';
			
			if (hasPermission($config['mod']['bumplock'], $board['uri'], $this->mod))
				if ($this->sage)
					$built .= ' <a title="'._('Allow thread to be bumped').'" href="?/' . secure_link($board['dir'] . 'bumpunlock/' . $this->id) . '">' . $config['mod']['link_bumpunlock'] . '</a>';
				else
					$built .= ' <a title="'._('Prevent thread from being bumped').'" href="?/' . secure_link($board['dir'] . 'bumplock/' . $this->id) . '">' . $config['mod']['link_bumplock'] . '</a>';
			
			// Lock
			if (hasPermission($config['mod']['lock'], $board['uri'], $this->mod))
				if ($this->locked)
					$built .= ' <a title="'._('Unlock thread').'" href="?/' . secure_link($board['dir'] . 'unlock/' . $this->id) . '">' . $config['mod']['link_unlock'] . '</a>';
				else
					$built .= ' <a title="'._('Lock thread').'" href="?/' . secure_link($board['dir'] . 'lock/' . $this->id) . '">' . $config['mod']['link_lock'] . '</a>';
			
			if (hasPermission($config['mod']['move'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Move thread to another board').'" href="?/' . $board['dir'] . 'move/' . $this->id . '">' . $config['mod']['link_move'] . '</a>';
			
			// Edit post
			if (hasPermission($config['mod']['editpost'], $board['uri'], $this->mod))
				$built .= ' <a title="'._('Edit post').'" href="?/' . $board['dir'] . 'edit' . ($config['mod']['raw_html_default'] ? '_raw' : '') . '/' . $this->id . '">' . $config['mod']['link_editpost'] . '</a>';
			
			if (!empty($built))
				$built = '<span class="controls op">' . $built . '</span>';
		}
		return $built;
	}
	
	public function ratio() {
		return fraction($this->filewidth, $this->fileheight, ':');
	}
	
	public function build($index=false, $isnoko50=false) {
		global $board, $config, $debug;
		
		$hasnoko50 = $this->postCount() >= $config['noko50_min'];
		
		$built = Element('post_thread.html', array('config' => $config, 'board' => $board, 'post' => &$this, 'index' => $index, 'hasnoko50' => $hasnoko50, 'isnoko50' => $isnoko50));
		
		return $built;
	}
};

