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

function doBoardListPart($list, $root, &$boards) {
	global $config;
	
	$body = '';
	foreach ($list as $key => $board) {
		if (is_array($board))
			$body .= ' <span class="sub" data-description="' . $key . '">[' . doBoardListPart($board, $root, $boards) . ']</span> ';
		else {
			if (gettype($key) == 'string') {
				$body .= ' <a href="' . $board . '">' . $key . '</a> /';
			} else {
				$title = '';
				if (isset ($boards[$board])) {
					$title = ' title="'.$boards[$board].'"';
				}
				
				$body .= ' <a href="' . $root . $board . '/' . $config['file_index'] . '"'.$title.'>' . $board . '</a> /';
			}
		}
	}
	$body = preg_replace('/\/$/', '', $body);
	
	return $body;
}

function createBoardlist($mod=false) {
	global $config;
	
	if (!isset($config['boards'])) return array('top'=>'','bottom'=>'');
	
	$xboards = listBoards();
	$boards = array();
	foreach ($xboards as $val) {
		$boards[$val['uri']] = $val['title'];
	}

	$body = doBoardListPart($config['boards'], $mod?'?/':$config['root'], $boards);

	if ($config['boardlist_wrap_bracket'] && !preg_match('/\] $/', $body))
		$body = '[' . $body . ']';
	
	$body = trim($body);

	// Message compact-boardlist.js faster, so that page looks less ugly during loading
	$top = "<script type='text/javascript'>if (typeof do_boardlist != 'undefined') do_boardlist();</script>";
	
	return array(
		'top' => '<div class="boardlist">' . $body . '</div>' . $top,
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
		echo('Error: ' . $message . "\n");
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		die();
	}

	if ($config['debug'] && isset($db_error)) {
		$debug_stuff = array_combine(array('SQLSTATE', 'Error code', 'Error message'), $db_error);
	}

	if ($config['debug']) {
		$debug_stuff['backtrace'] = debug_backtrace();
	}

	if (isset($_POST['json_response'])) {
		header('Content-Type: text/json; charset=utf-8');
		die(json_encode(array(
			'error' => $message
		)));
	}
	else {
		header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
	}

	$pw = $config['db']['password'];
	$debug_callback = function($item) use (&$debug_callback, $pw) {
		if (is_array($item)) {
			$item = array_filter($item, $debug_callback);
		}
		return ($item !== $pw || !$pw);
	};


	if ($debug_stuff) 
		$debug_stuff = array_filter($debug_stuff, $debug_callback);

	die(Element($config['file_page_template'], array(
		'config' => $config,
		'title' => _('Error'),
		'subtitle' => _('An error has occured.'),
		'body' => Element($config['file_error'], array(
			'config' => $config,
			'message' => $message,
			'mod' => $mod,
			'board' => isset($board) ? $board : false,
			'debug' => $config['debug'] ? (is_array($debug_stuff) ? str_replace("\n", '&#10;', utf8tohtml(print_r($debug_stuff, true))) : utf8tohtml($debug_stuff)) : null
		))
	)));
}

function loginForm($error=false, $username=false, $redirect=false) {
	global $config;
	
	die(Element($config['file_page_template'], array(
		'index' => $config['root'],
		'title' => _('Login'),
		'config' => $config,
		'body' => Element($config['file_login'], array(
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
	$body = preg_replace('@<br/?>@i', '  ', $body);
	
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

function bidi_cleanup($data) {
	// Closes all embedded RTL and LTR unicode formatting blocks in a string so that
	// it can be used inside another without controlling its direction.

	$explicits	= '\xE2\x80\xAA|\xE2\x80\xAB|\xE2\x80\xAD|\xE2\x80\xAE';
	$pdf		= '\xE2\x80\xAC';
	
	preg_match_all("!$explicits!",	$data, $m1, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
	preg_match_all("!$pdf!", 	$data, $m2, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
	
	if (count($m1) || count($m2)){
	
		$p = array();
		foreach ($m1 as $m){ $p[$m[0][1]] = 'push'; }
		foreach ($m2 as $m){ $p[$m[0][1]] = 'pop'; }
		ksort($p);
	
		$offset = 0;
		$stack = 0;
		foreach ($p as $pos => $type){
	
			if ($type == 'push'){
				$stack++;
			}else{
				if ($stack){
					$stack--;
				}else{
					# we have a pop without a push - remove it
					$data = substr($data, 0, $pos-$offset)
						.substr($data, $pos+3-$offset);
					$offset += 3;
				}
			}
		}
	
		# now add some pops if your stack is bigger than 0
		for ($i=0; $i<$stack; $i++){
			$data .= "\xE2\x80\xAC";
		}
	
		return $data;
	}
	
	return $data;
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

		if (isset($this->files) && $this->files) {
			$this->files = is_string($this->files) ? json_decode($this->files) : $this->files;
			// Compatibility for posts before individual file hashing
			foreach ($this->files as $i => &$file) {
				if (empty($file)) {
					unset($this->files[$i]);
					continue;
				}
				if (!isset($file->hash))
					$file->hash = $this->filehash;
			}
		}
		
		$this->subject = utf8tohtml($this->subject);
		$this->name = utf8tohtml($this->name);
		$this->mod = $mod;
		$this->root = $root;
		
		if ($this->embed)
			$this->embed = embed_html($this->embed);
		
		$this->modifiers = extract_modifiers($this->body_nomarkup);
		
		if ($config['always_regenerate_markup']) {
			$this->body = $this->body_nomarkup;
			markup($this->body);
		}
		
		if ($this->mod)
			// Fix internal links
			// Very complicated regex
			$this->body = preg_replace(
				'/<a((([a-zA-Z]+="[^"]+")|[a-zA-Z]+=[a-zA-Z]+|\s)*)href="' . preg_quote($config['root'], '/') . '(' . sprintf(preg_quote($config['board_path'], '/'), $config['board_regex']) . ')/u',
				'<a $1href="?/$4',
				$this->body
			);
	}
	public function link($pre = '', $page = false) {
		global $config, $board;
		
		return $this->root . $board['dir'] . $config['dir']['res'] . link_for((array)$this, $page == '50') . '#' . $pre . $this->id;
	}
	
	public function build($index=false) {
		global $board, $config;
		
		return Element($config['file_post_reply'], array('config' => $config, 'board' => $board, 'post' => &$this, 'index' => $index, 'mod' => $this->mod));
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
		
		if (isset($this->files))
			$this->files = is_string($this->files) ? json_decode($this->files) : $this->files;
		
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
		
		if ($config['always_regenerate_markup']) {
			$this->body = $this->body_nomarkup;
			markup($this->body);
		}
		
		if ($this->mod)
			// Fix internal links
			// Very complicated regex
			$this->body = preg_replace(
				'/<a((([a-zA-Z]+="[^"]+")|[a-zA-Z]+=[a-zA-Z]+|\s)*)href="' . preg_quote($config['root'], '/') . '(' . sprintf(preg_quote($config['board_path'], '/'), $config['board_regex']) . ')/u',
				'<a $1href="?/$4',
				$this->body
			);
	}
	public function link($pre = '', $page = false) {
		global $config, $board;
		
		return $this->root . $board['dir'] . $config['dir']['res'] . link_for((array)$this, $page == '50') . '#' . $pre . $this->id;
	}
	public function add(Post $post) {
		$this->posts[] = $post;
	}
	public function postCount() {
		   return count($this->posts) + $this->omitted;
	}
	public function build($index=false, $isnoko50=false) {
		global $board, $config, $debug;
		
		$hasnoko50 = $this->postCount() >= $config['noko50_min'];
		
		event('show-thread', $this);

		$file = ($index && $config['file_board']) ? $config['file_post_thread_fileboard'] : $config['file_post_thread'];
		$built = Element($file, array('config' => $config, 'board' => $board, 'post' => &$this, 'index' => $index, 'hasnoko50' => $hasnoko50, 'isnoko50' => $isnoko50, 'mod' => $this->mod));
		
		return $built;
	}
};

