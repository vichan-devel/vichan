<?php
	require 'info.php';
	
	function jsframeset_build($action, $settings, $board) {
		// Possible values for $action:
		//	- all (rebuild everything, initialization)
		//	- news (news has been updated)
		//	- boards (board list changed)
		
		JSFrameset::build($action, $settings);
	}

	// Wrap functions in a class so they don't interfere with normal Tinyboard operations
	class JSFrameset {
		public static function build($action, $settings) {
			global $config;
			
			if ($action == 'all' || $action == 'boards')
				file_write($config['dir']['home'] . $settings['file_sidebar'], JSFrameset::sidebar($settings));
		}
		
		// Build sidebar
		public static function sidebar($settings) {
			global $config, $board;
			

			// Create board list



			return Element('themes/js_frameset/sidebar.html', Array(
				'settings' => $settings,
				'config' => $config,
				'boardlist' => createSidebarBoardlist()
			));
		}


	};



	function doSidebarBoardListPart($list, &$boards){
		
		global $config;
	
		$body = '';
		foreach ($list as $key => $board) {
			if (is_array($board))
				$body .= '<ul style="margin-bottom: 1em;">' . doSidebarBoardListPart($board, $boards) . '</ul> ';
			else {
				if (gettype($key) == 'string') {
					$body .= '<li><a href="' . $board . '">' . $key . '</a></li>';
				} else if (isset ($boards[$board])) {
					$body .= '<li><a href="' . $config['root'] . $board . '/' . $config['file_index'] . '" title="'.$boards[$board].'">' . $boards[$board] . '</a></li>';
				}
			}
		}
		$body = preg_replace('/\/$/', '', $body);
	
		return $body;
	}


	function createSidebarBoardlist() {
		global $config;
	
		if (!isset($config['boards'])) return "";
	
		$xboards = listBoards();
		$boards = array();
		foreach ($xboards as $val) {
			$boards[$val['uri']] = $val['title'];
		}

		$body = doSidebarBoardListPart($config['boards'], $boards);
		return trim($body);
	}
	
		

		/*

	function doSidebarBoardListPart($list, &$boards) {
		global $config;
	
		$body = '';
		foreach ($list as $key => $board) {
			if (is_array($board))
				$body .= '<ul>' . doSidebarBoardListPart($board, $boards) . '</ul> ';
			else {
				if (gettype($key) == 'string') {
					$body .= '<li><a href="' . $board . '">' . $key . '</a></li>';
				} else {
					$title = '';
					$fullname = $board;
					if (isset ($boards[$board])) {
						$title = ' title="'.$boards[$board].'"';
						$fullname = $boards[$board];
						$body .= '</li><a href="' . $config['root'] . $board . '/' . $config['file_index'] . '"'.$title.'>' . $fullname . '</a></li>';
					}
				}
			}
		}
		$body = preg_replace('/\/$/', '', $body);
	
		return $body;
	}

	function createSidebarBoardlist() {
		global $config;
	
		if (!isset($config['boards'])) return "";
	
		$xboards = listBoards();
		$boards = array();
		foreach ($xboards as $val) {
			$boards[$val['uri']] = $val['title'];
		}

		$body = doSidebarBoardListPart($config['boards'], $boards);
		return trim($body);
	}


	*/



?>

